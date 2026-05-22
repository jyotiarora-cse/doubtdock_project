const express = require('express');
const http = require('http');
require('dotenv').config();
const Groq = require('groq-sdk');
const groq = new Groq({ apiKey: process.env.GROQ_API_KEY });
const { Server } = require('socket.io');
const mysql = require('mysql2');

const app    = express();
const server = http.createServer(app);

const io = new Server(server, { cors: { origin: "*" } });

app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type');
    next();
});
app.use(express.json());

const db = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'doubtdock',
    waitForConnections: true,
    connectionLimit: 10
});

const dbQuery = (sql, params) => new Promise((resolve, reject) => {
    db.query(sql, params, (err, results) => {
        if (err) reject(err);
        else resolve(results);
    });
});

db.getConnection((err, conn) => {
    if (err) console.error("❌ Database Connection Failed:", err.message);
    else { console.log("✅ Database Connected via Pool!"); conn.release(); }
});

const roomQuestions = {};
const roomMentors   = {};
const onlineMentors = {};

// ── Online Mentors API — rating doubts table se ──
app.get('/online-mentors', (req, res) => {
    const mentorList = Object.values(onlineMentors);
    if (mentorList.length === 0) return res.json([]);

    const ids = mentorList.map(m => m.id).filter(Boolean);
    if (ids.length === 0) return res.json(mentorList);

    db.query(
        `SELECT   mentor_id,
                  ROUND(AVG(rating), 1) AS rating,
                  COUNT(*)              AS review_count
         FROM     doubts
         WHERE    mentor_id IN (?)
           AND    rating IS NOT NULL
         GROUP BY mentor_id`,
        [ids],
        (err, rows) => {
            if (err) {
                console.error("❌ Rating fetch error:", err.message);
                return res.json(mentorList);
            }
            const ratingMap = {};
            rows.forEach(r => { ratingMap[r.mentor_id] = { rating: r.rating, review_count: r.review_count }; });
            const enriched = mentorList.map(m => ({
                ...m,
                rating:       ratingMap[m.id]?.rating       ?? null,
                review_count: ratingMap[m.id]?.review_count ?? 0
            }));
            res.json(enriched);
        }
    );
});

// ── Notify New Doubt ──
app.get('/notify-new-doubt/:doubtId', (req, res) => {
    const doubtId = parseInt(req.params.doubtId);
    db.query("SELECT doubt_id, subject, topic, description FROM doubts WHERE doubt_id = ?", [doubtId], (err, rows) => {
        if (err || rows.length === 0) return res.status(500).send("Error");
        const doubt = rows[0];
        const fullQuestion = `Topic: ${doubt.topic}. ${doubt.description || ''}`.trim();
        roomQuestions[String(doubtId)] = fullQuestion;
        console.log(`📝 Room #${doubtId} question saved: "${fullQuestion}"`);
        io.emit('new_doubt', doubt);
        res.status(200).send("Broadcasted");
    });
});

// ── Notify Claim ──
app.get('/notify-claim/:doubtId', (req, res) => {
    const doubtId = String(req.params.doubtId);
    io.to(doubtId).emit('mentor_found', { status: 'ready' });
    io.emit('doubt_claimed', { doubt_id: parseInt(doubtId) });
    res.status(200).send("Notified");
});

function isCasualMessage(msg) {
    if (!msg) return true;
    const trimmed = msg.trim();
    // Broad casual pattern for greetings and simple acknowledgments
    const casualPattern = /^(hi|hello|hey|ok+|okay|thanks|thank\s+you|sure|yes|no|hmm+|got\s+it|alright|noted|welcome|np|no\s+problem|good|great|understood|wait|one\s+moment|hold\s+on|please\s+wait|coming|sure\s+thing|absolutely|of\s+course|definitely|sounds\s+good|perfect|nice|cool|wow|ohh?|ahh?|ohk+|hii+|heyy+|bye|goodbye|see\s+you|talk\s+later|brb|back|hello\s+there|hi\s+there)(\s+[a-zA-Z]+){0,2}[\s!?.,-]*$/i;
    if (casualPattern.test(trimmed)) return true;

    // Expanded list of educational keywords
    const hasEducationalContent = /\b(what|how|why|when|where|which|explain|define|difference|example|concept|function|work|mean|kya|kaise|kyun|matlab|batao|samjhao|between|type|sort|algorithm|data|structure|program|code|error|output|input|loop|array|tree|graph|stack|queue|link|node|class|object|method|search|binary|linear|sorting|complexity|time|space|machine|human|effort|reduce|physics|chemistry|math|logic|reason|solve|question|problem)\b/i.test(trimmed);
    
    // If it's short and has no educational keywords, consider it casual
    if (!hasEducationalContent && trimmed.length < 40) return true;
    
    return false;
}

// ── Rating save — doubts table ke rating column mein ──
async function saveRatingToDoubt(doubtId, accuracyScore) {
    if (!doubtId || accuracyScore === undefined || accuracyScore === null) {
        console.warn("⚠️ saveRatingToDoubt: missing data, skipping.");
        return;
    }
    // AI 0-10 score → 1-5 stars
    const starRating = Math.min(5, Math.max(1, Math.round((accuracyScore / 10) * 4) + 1));
    try {
        await dbQuery(`UPDATE doubts SET rating = ? WHERE doubt_id = ?`, [starRating, parseInt(doubtId)]);
        console.log(`💾 Rating saved — doubt #${doubtId}: AI ${accuracyScore}/10 → ${starRating} stars`);
    } catch (err) {
        console.error("❌ saveRatingToDoubt error:", err.message);
    }
}

// ── Socket.io ──
io.on('connection', (socket) => {
    console.log(`🔌 New Connection: ${socket.id}`);

    socket.on('mentor_online', (data) => {
        onlineMentors[socket.id] = { id: data.id, name: data.name, subject: data.subject };
        io.emit('mentors_updated');
        console.log(`🟢 Mentor online: ${data.name} (${data.subject})`);
    });

    // ── Join Chat Room ──
    // chat_ui.php ab { doubtId, mentorId, studentId } object bhejta hai
    socket.on('join_chat', (data) => {
        const doubtId   = typeof data === 'object' ? String(data.doubtId)  : String(data);
        const mentorId  = typeof data === 'object' ? (data.mentorId  || null) : null;
        const studentId = typeof data === 'object' ? (data.studentId || null) : null;

        socket.join(doubtId);
        console.log(`🏠 Socket ${socket.id} joined Room #${doubtId}`);

        if (mentorId) {
            roomMentors[doubtId] = { mentorId, studentId };
            console.log(`✅ Mentor (ID: ${mentorId}) is now active in Room #${doubtId}`);
        } else {
            console.log(`ℹ️ Student (ID: ${studentId}) is now active in Room #${doubtId}`);
        }
    });

    socket.on('send_message', async (data) => {
        const room = String(data.doubtId);

        // Student message
        if (data.senderType && data.senderType.toLowerCase() === 'student') {
            if (!isCasualMessage(data.message)) {
                roomQuestions[room] = data.message;
                console.log(`💬 Student question updated for room ${room}: "${data.message}"`);
            }
            io.to(room).emit('receive_message', { ...data, messageStatus: 'normal' });
            saveToDb(data);
            return;
        }

        // Mentor message
        if (data.senderType && data.senderType.toLowerCase() === 'mentor') {

            if (isCasualMessage(data.message)) {
                io.to(room).emit('receive_message', { ...data, messageStatus: 'correct' });
                console.log(`💬 Casual mentor message — AI skipped: "${data.message}"`);
                saveToDb(data);
                return;
            }

            let studentQuestion = roomQuestions[room] || null;

            // ── Database Fallback — if memory is empty (server restart) ──
            if (!studentQuestion) {
                try {
                    const rows = await dbQuery("SELECT topic, description FROM doubts WHERE doubt_id = ?", [parseInt(room)]);
                    if (rows && rows.length > 0) {
                        studentQuestion = `Topic: ${rows[0].topic}. ${rows[0].description || ''}`.trim();
                        roomQuestions[room] = studentQuestion; // Update memory
                        console.log(`♻️ Room #${room} question restored from DB for AI.`);
                    }
                } catch (dbErr) {
                    console.error("❌ DB Fallback Error:", dbErr.message);
                }
            }

            if (!studentQuestion) {
                io.to(room).emit('receive_message', { ...data, messageStatus: 'correct' });
                console.log(`💬 No student question found in memory or DB — AI skipped`);
                saveToDb(data);
                return;
            }

            try {
                // Inform the mentor that AI is starting
                socket.emit('ai_thinking', { doubtId: room });
                
                console.log(`🤖 AI checking mentor answer in room ${room}...`);
                console.log(`   Student asked: "${studentQuestion}"`);
                console.log(`   Mentor said:   "${data.message}"`);

                const completion = await groq.chat.completions.create({
                    model: "llama-3.3-70b-versatile",
                    max_tokens: 1000,
                    messages: [
                        {
                            role: "system",
                            content: "You are a JSON-only responder. No extra text. No markdown. No code blocks. Your output must start with { and end with }."
                        },
                        {
                            role: "user",
                            content: `
A student asked a doubt and a mentor gave an educational answer.
Your job is to verify if the mentor's answer is correct and appropriate.

Student's question/doubt: "${studentQuestion}"
Mentor's answer: "${data.message}"

IMPORTANT RULES:
- Only evaluate EDUCATIONAL/TECHNICAL answers — not greetings or casual chat
- Detect the language of the student's question (Hindi, English, Hinglish, etc.)
- "correctedAnswer" MUST be in the SAME LANGUAGE as the student's question
- "simplifiedExplanation" MUST also be in the SAME LANGUAGE as the student's question
- "mentorFeedback" MUST always be in ENGLISH only — clear and professional
- "mistake" MUST always be in ENGLISH only
- correctedAnswer should ONLY answer what the student asked — nothing extra
- If mentor's answer is about a completely wrong topic, isCorrect = false
- If mentor's message is a greeting or casual chat, mark isCorrect = true with accuracyScore = 10

Respond ONLY in this exact JSON format:
{
  "isCorrect": true or false,
  "accuracyScore": 0-10,
  "simplicityScore": 0-10,
  "isTooComplex": true or false,
  "mistake": "exactly what was wrong in English (empty string if correct)",
  "correctedAnswer": "correct and simple answer in SAME LANGUAGE as student's question",
  "mentorFeedback": "detailed feedback for mentor in ENGLISH — what went wrong, why it was wrong, what the correct answer should have been",
  "simplifiedExplanation": "simpler version in SAME LANGUAGE as student's question (empty string if not needed)"
}`
                        }
                    ]
                });

                let raw = completion.choices[0].message.content;
                
                // Robust JSON Extraction: Find the first { and last }
                const firstBrace = raw.indexOf('{');
                const lastBrace  = raw.lastIndexOf('}');
                if (firstBrace !== -1 && lastBrace !== -1) {
                    raw = raw.substring(firstBrace, lastBrace + 1);
                }
                
                const result = JSON.parse(raw);

                console.log(`   AI result: isCorrect=${result.isCorrect}, accuracy=${result.accuracyScore}/10`);

                // ── Rating save karo doubts table mein ──
                await saveRatingToDoubt(data.doubtId, result.accuracyScore);

                // Student dashboard pe rating refresh trigger karo
                io.emit('mentors_updated');

                if (!result.isCorrect) {
                    socket.emit('receive_message', { ...data, messageStatus: 'wrong' });
                    socket.emit('receive_message', { ...data, message: result.correctedAnswer, messageStatus: 'corrected_mentor' });
                    socket.to(room).emit('receive_message', { ...data, message: result.correctedAnswer, messageStatus: 'normal' });
                    
                    // Logic Fix: Database mein bhi corrected message save ho
                    data.message = result.correctedAnswer;
                    console.log(`❌ Wrong in room ${room} — saving corrected version to DB`);

                } else if (result.isTooComplex && result.simplifiedExplanation) {
                    socket.emit('receive_message', { ...data, messageStatus: 'complex' });
                    socket.emit('receive_message', { ...data, message: result.simplifiedExplanation, messageStatus: 'corrected_mentor' });
                    socket.to(room).emit('receive_message', { ...data, message: result.simplifiedExplanation, messageStatus: 'normal' });
                    
                    // Logic Fix: Database mein simplified message save ho
                    data.message = result.simplifiedExplanation;
                    console.log(`⚠️ Complex in room ${room} — saving simplified version to DB`);

                } else {
                    io.to(room).emit('receive_message', { ...data, messageStatus: 'correct' });
                    console.log(`✅ Correct in room ${room}`);
                }

                socket.emit('ai_feedback', { ...result, studentQuestion });

            } catch (err) {
                console.error("❌ Groq Error:", err.message);
                io.to(room).emit('receive_message', { ...data, messageStatus: 'normal' });
            }

            saveToDb(data);
            return;
        }

        io.to(room).emit('receive_message', data);
        saveToDb(data);
    });

    socket.on('typing', (data) => {
        const room = String(data.doubtId);
        console.log(`⌨️ Broadcast: ${data.senderName} is typing in room ${room}`);
        io.to(room).emit('display_typing', data);
    });

    socket.on('stop_typing', (data) => {
        const room = String(data.doubtId);
        io.to(room).emit('hide_typing', data);
    });

    socket.on('close_chat', (data) => {
        const room = String(data.doubtId);
        delete roomQuestions[room];
        delete roomMentors[room];
        io.to(room).emit('chat_closed', data);
        console.log(`🔴 Chat closed in Room #${room}`);
    });

    socket.on('disconnect', () => {
        if (onlineMentors[socket.id]) {
            console.log(`🔴 Mentor offline: ${onlineMentors[socket.id].name}`);
            delete onlineMentors[socket.id];
            io.emit('mentors_updated');
        }
        console.log(`❌ Disconnected: ${socket.id}`);
    });
});

// ── Chat message DB save ──
function saveToDb(data) {
    // Saari required fields check karo — koi bhi missing ho toh skip
    if (!data.doubtId || !data.senderId || !data.senderType || !data.message) {
        console.warn("⚠️ saveToDb skipped — missing fields:", {
            doubtId:    data.doubtId,
            senderId:   data.senderId,
            senderType: data.senderType,
            message:    data.message ? "ok" : "MISSING"
        });
        return;
    }
    db.query(
        "INSERT INTO chat_messages (doubt_id, sender_id, sender_type, message) VALUES (?, ?, ?, ?)",
        [data.doubtId, data.senderId, data.senderType, data.message],
        (err) => {
            if (err) console.error("❌ DB Save Error:", err.message);
            else console.log(`✅ Message saved — room ${data.doubtId}, sender ${data.senderId} (${data.senderType})`);
        }
    );
}

server.listen(3000, () => console.log('🚀 DoubtDock server running on http://localhost:3000'));
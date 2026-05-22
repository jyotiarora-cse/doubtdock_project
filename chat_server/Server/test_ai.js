require('dotenv').config();
const Groq = require('groq-sdk');
const groq = new Groq({ apiKey: process.env.GROQ_API_KEY });

async function testAI() {
    const studentQuestion = "What is a binary tree?";
    const mentorMessage = "A binary tree is a data structure where each node has at most two children.";

    console.log("🤖 Testing Groq AI...");
    try {
        const completion = await groq.chat.completions.create({
            model: "llama-3.3-70b-versatile",
            messages: [
                { role: "system", content: "You are a JSON-only responder." },
                { role: "user", content: `Question: ${studentQuestion}\nAnswer: ${mentorMessage}\nEvaluate in JSON.` }
            ]
        });
        console.log("✅ AI Response:", completion.choices[0].message.content);
    } catch (err) {
        console.error("❌ AI Error:", err.message);
    }
}

testAI();

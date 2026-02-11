<?php
session_start();
include('db_connection.php'); // Check karein ki file name db.php hai ya db_connection.php
include('mail_function.php');

$mentor_id = $_SESSION['user_id'] ?? 201; 

// --- ACTION 1: CLAIM DOUBT ---
if(isset($_GET['action']) && $_GET['action'] == 'claim') {
    // FIX: Humne variable name doubt_id rakha hai jo table column se match karega
    $doubt_id = $_GET['id'];

    // FIX: Query mein 'id' ki jagah 'doubt_id' use kiya hai
    $check = mysqli_query($conn, "SELECT status FROM doubts WHERE doubt_id = '$doubt_id'");
    $row = mysqli_fetch_assoc($check);

    if($row && $row['status'] == 'Pending') {
        // FIX: UPDATE query mein bhi 'doubt_id' column use kiya hai
        $update = "UPDATE doubts SET status='Claimed', mentor_id='$mentor_id', claimed_at=NOW() WHERE doubt_id='$doubt_id'";
        mysqli_query($conn, $update);
        header("Location: mantor_dashboard.php?status=success_claimed");
    } else {
        echo "<script>alert('Doubt already taken or not found!'); window.location='mantor_dashboard.php';</script>";
    }
}

// --- ACTION 2: SOLVE DOUBT ---
if(isset($_POST['solve_btn'])) {
    $doubt_id = $_POST['doubt_id']; // Hidden input se aayega
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);

    // FIX: WHERE clause mein 'id' ki jagah 'doubt_id'
    $sql = "UPDATE doubts SET answer='$answer', status='Solved' WHERE doubt_id='$doubt_id' AND mentor_id='$mentor_id'";
    
    if(mysqli_query($conn, $sql)) {
        // FIX: Yahan bhi 'doubt_id'
        $res = mysqli_query($conn, "SELECT student_id FROM doubts WHERE doubt_id='$doubt_id'");
        $s = mysqli_fetch_assoc($res);
        
         
        // isliye hum abhi sirf dashboard redirect kar rahe hain.
        header("Location: mantor_dashboard.php?status=solved");
    }
}
?>
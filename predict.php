<?php
require 'vendor/autoload.php';

use Phpml\ModelManager;

// PROCESS FORM WHEN SUBMITTED
$grade = null;
$risk = null;
$tips = null;
$sentiment = null;
$result = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // GET QUIZ SCORES + OVER VALUES
    $scores = [];
    $overs = [];

    foreach ($_POST as $key => $value) {
        if (strpos($key, "quiz_") === 0 && !str_contains($key, "over")) {
            $scores[] = (float)$value;
        }
        if (strpos($key, "quiz_over_") === 0) {
            $overs[] = (float)$value;
        }
    }

    $totalScore = 0;
    $totalOver = 0;

    for ($i = 0; $i < count($scores); $i++) {
        $totalScore += $scores[$i];
        $totalOver += $overs[$i];
    }

    $attendance = $_POST["attendance"];
    $hours = $_POST["hours"];
    $notes = $_POST["notes"];

    // CALCULATE AVERAGE QUIZ PERCENTAGE
    $quizPercent = ($totalOver > 0) ? ($totalScore / $totalOver) * 100 : 0;
    
    // Load and use ML model
    $modelManager = new ModelManager();
    $regression = $modelManager->restoreFromFile('model.zip');
    
    // Predict
    $result = $regression->predict([$quizPercent, $attendance, $hours]);
    $grade = round($result, 2);

    // Determine risk
    if ($grade < 75) $risk = "High";
    else if ($grade < 85) $risk = "Medium";
    else $risk = "Low";

    // Study tips
    if ($risk == "High") 
        $tips = "Improve your study routine and attend classes regularly.";
    else if ($risk == "Medium") 
        $tips = "Good job! Keep studying consistently for higher results.";
    else 
        $tips = "Excellent! Maintain your strong academic performance.";

    // ENHANCED SENTIMENT ANALYSIS
    $sentiment = "neutral";
    if (!empty($notes)) {
        $lower = strtolower($notes);
        
        // More comprehensive word lists
        $negativeWords = ['sad', 'tired', 'stress', 'stressful', 'overwhelmed', 'anxious', 'anxiety', 
                          'depressed', 'exhausted', 'burnout', 'burned out', 'struggling', 'difficult',
                          'hard', 'challenging', 'frustrated', 'frustrating', 'worried', 'worry',
                          'confused', 'bored', 'boring', 'hate', 'hated', 'dislike', 'angry', 'mad',
                          'afraid', 'scared', 'fear', 'failure', 'fail', 'failed', 'bad', 'poor',
                          'terrible', 'awful', 'horrible', 'dread', 'dreading'];
        
        $positiveWords = ['happy', 'motivated', 'excited', 'great', 'good', 'excellent', 'awesome',
                          'fantastic', 'wonderful', 'amazing', 'progress', 'progressing', 'learning',
                          'improving', 'improved', 'confident', 'confidence', 'optimistic', 'hope',
                          'hopeful', 'energetic', 'energized', 'productive', 'focused', 'engaged',
                          'enjoy', 'enjoying', 'loving', 'love', 'interested', 'interesting',
                          'satisfied', 'satisfaction', 'proud', 'accomplished', 'success', 'successful',
                          'easy', 'easier', 'better', 'best', 'improvement', 'achievement'];
        
        // Count matches
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            if (str_contains($lower, $word)) {
                $positiveCount++;
            }
        }
        
        foreach ($negativeWords as $word) {
            if (str_contains($lower, $word)) {
                $negativeCount++;
            }
        }
        
        // Determine sentiment
        if ($positiveCount > $negativeCount) {
            $sentiment = "positive";
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = "negative";
        } else {
            // Check for neutral indicators
            $neutralIndicators = ['normal', 'regular', 'usual', 'typical', 'average', 'ok', 'okay', 
                                  'fine', 'alright', 'ordinary', 'standard', 'moderate'];
            foreach ($neutralIndicators as $word) {
                if (str_contains($lower, $word)) {
                    $sentiment = "neutral";
                    break;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Academic Performance Predictor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1a1a2e;
            --card-bg: #22223b;
            --neon-blue: #00ffff;
            --neon-purple: #9a40ff;
            --text-color: #f0f8ff;
            --input-bg: #333355;
            --input-text: #ffffff;
            --border-color: rgba(0, 255, 255, 0.3);
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #0f0f1c 100%);
            font-family: 'Space Mono', monospace;
            color: var(--text-color);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: repeating-linear-gradient(0deg, var(--dark-bg), var(--dark-bg) 1px, rgba(0, 255, 255, 0.05) 1px, rgba(0, 255, 255, 0.05) 2px);
            opacity: 0.8;
            pointer-events: none;
        }
        
        .container {
            padding-top: 50px;
            position: relative;
            z-index: 1;
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.4);
        }
        
        .card-body {
            padding: 40px;
        }
        
        .card-title {
            color: var(--neon-blue);
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 30px;
            text-shadow: 0 0 8px rgba(0, 255, 255, 0.8);
            text-align: center;
            position: relative;
        }
        
        .card-title::after {
            content: "🎓";
            position: absolute;
            right: -30px;
            top: 0;
            font-size: 1.5rem;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; text-shadow: 0 0 5px var(--neon-blue); }
            50% { opacity: 0.6; text-shadow: 0 0 15px var(--neon-blue); }
        }
        
        .form-label {
            font-weight: 400;
            color: var(--neon-blue);
            font-size: 1rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .icon {
            margin-right: 10px;
            color: var(--neon-blue);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--neon-blue);
            transition: all 0.3s ease;
            background-color: var(--input-bg); 
            color: var(--input-text); 
            font-size: 1rem;
            padding: 12px 15px;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
            opacity: 1;
        }
        
        .form-control:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.8);
            background-color: #3f3f6e;
            transform: scale(1.01);
        }

        .btn {
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
            padding: 12px 25px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--neon-purple), var(--neon-blue));
            border: none;
            color: var(--text-color);
            text-shadow: 0 0 5px #00ffff;
            box-shadow: 0 4px 20px rgba(0, 255, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.7);
            filter: brightness(1.2);
        }
        
        .btn-secondary {
            background: var(--card-bg);
            color: var(--neon-blue);
            border: 2px solid var(--neon-blue);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background-color: #333355;
            color: var(--text-color);
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #d9534f, #ff6b6b);
            border: none;
            color: var(--text-color);
            box-shadow: 0 4px 20px rgba(217, 83, 79, 0.3);
        }
        
        .btn-danger:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(217, 83, 79, 0.7);
            filter: brightness(1.2);
        }
        
        .quiz-row {
            animation: fadeIn 0.5s ease-out;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px dashed rgba(0, 255, 255, 0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .result-section {
            background: var(--dark-bg);
            color: var(--neon-blue);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            border: 2px solid var(--neon-blue);
            box-shadow: 0 0 25px rgba(0, 255, 255, 0.5), inset 0 0 10px rgba(0, 255, 255, 0.3);
            position: relative;
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .result-section h5 {
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .result-section span {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            background: rgba(0, 255, 255, 0.1);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.1);
            color: var(--text-color);
            margin-top: 15px;
        }
        
        .alert-info {
            background: rgba(0, 255, 255, 0.15);
            color: var(--neon-blue);
            border-left: 5px solid var(--neon-blue);
        }
        
        .alert-secondary {
            background: rgba(154, 64, 255, 0.15);
            color: var(--neon-purple);
            border-left: 5px solid var(--neon-purple);
        }
        
        .fw-bold {
            font-weight: 700;
        }
        .text-danger { color: #ff6b6b !important; }
        .text-warning { color: #ffe66d !important; }
        .text-success { color: #1dd1a1 !important; }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-actions .btn {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-title { font-size: 1.8rem; }
            .btn { font-size: 0.9rem; padding: 10px 20px; }
            .quiz-row { padding: 10px; }
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Academic Performance Predictor</h3>

                    <form method="POST" id="predictionForm">
                        <div id="quiz-container">
                            <div class="mb-3 row quiz-row">
                                <div class="col">
                                    <label class="form-label">Quiz 1 Score</label>
                                    <input type="number" class="form-control" name="quiz_1" placeholder="Score" required>
                                </div>
                                <div class="col">
                                    <label class="form-label">Quiz 1 Total (Over)</label>
                                    <input type="number" class="form-control" name="quiz_over_1" placeholder="Total" required>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-secondary mb-4" id="add-quiz-btn">+ Add More Quiz</button>

                        <div class="mb-4">
                            <label for="attendance" class="form-label">Attendance (%)</label>
                            <input type="number" class="form-control" name="attendance" id="attendance" placeholder="Enter your attendance" min="0" max="100" required>
                        </div>

                        <div class="mb-4">
                            <label for="hours" class="form-label">Study Hours per Day</label>
                            <input type="number" class="form-control" name="hours" id="hours" placeholder="Enter your study hours" min="0" max="24" required>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">Describe your study feelings / habits</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Type how you feel about studying today..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Predict</button>
                            <button type="button" class="btn btn-danger" id="reset-btn">Reset</button>
                        </div>
                    </form>

                    <?php if ($grade !== null): ?>
                    <div class="result-section mt-4">
                        <h5>Predicted Final Grade: <span class="fw-bold"><?= $grade ?></span></h5>
                        
                        <h5>Risk Level: 
                            <span class="fw-bold <?= $risk == 'High' ? 'text-danger' : ($risk == 'Medium' ? 'text-warning' : 'text-success') ?>">
                                <?= $risk ?>
                            </span>
                        </h5>

                        <div class="alert alert-info mt-3">
                            <strong>Study Tips:</strong><br>
                            <?= $tips ?>
                        </div>

                        <?php if ($sentiment): ?>
                        <div class="alert alert-secondary mt-2 text-center">
                            <strong>Sentiment Analysis:</strong> <?= ucfirst($sentiment) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let quizCount = 1;

function renumberQuizzes() {
    const rows = document.querySelectorAll("#quiz-container .quiz-row");
    quizCount = rows.length;

    rows.forEach((row, i) => {
        let num = i + 1;

        row.querySelector("label").innerText = `Quiz ${num} Score`;
        row.querySelector("input[name^='quiz_']").name = `quiz_${num}`;

        row.querySelectorAll("label")[1].innerText = `Quiz ${num} Total (Over)`;
        row.querySelector("input[name^='quiz_over_']").name = `quiz_over_${num}`;
    });
}

document.getElementById("add-quiz-btn").addEventListener("click", () => {
    quizCount++;
    let div = document.createElement("div");
    div.classList.add("mb-3", "row", "quiz-row");

    div.innerHTML = `
        <div class="col">
            <label class="form-label">Quiz ${quizCount} Score</label>
            <input type="number" class="form-control" name="quiz_${quizCount}" placeholder="Score" required>
        </div>
        <div class="col">
            <label class="form-label">Quiz ${quizCount} Total (Over)</label>
            <input type="number" class="form-control" name="quiz_over_${quizCount}" placeholder="Total" required>
        </div>
        <div class="col-auto d-flex align-items-end">
            <button type="button" class="btn btn-danger remove-quiz-btn">&times;</button>
        </div>
    `;

    document.querySelector("#quiz-container").appendChild(div);

    div.querySelector(".remove-quiz-btn").addEventListener("click", () => {
        div.remove();
        renumberQuizzes();
    });
});

// Reset button functionality
document.getElementById("reset-btn").addEventListener("click", () => {
    if (confirm("Are you sure you want to reset the form? All data will be lost.")) {
        document.getElementById("predictionForm").reset();
        
        // Remove all quiz rows except the first one
        const quizRows = document.querySelectorAll("#quiz-container .quiz-row");
        quizRows.forEach((row, index) => {
            if (index > 0) {
                row.remove();
            }
        });
        
        // Reset quiz count
        quizCount = 1;
        renumberQuizzes();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
</script>

</body>
</html>
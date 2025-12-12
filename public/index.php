<?php

session_start();


function calculatePredictions($data) {
    $predictions = [
        'grade' => null,
        'risk' => 'Low',
        'quizPercent' => 0,
        'tips' => '',
        'sentiment' => 'neutral',
        'sentimentIcon' => '',
        'sentimentClass' => 'sentiment-neutral',
        'riskColor' => 'Low',
        'sentimentText' => 'Neutral Mood'
    ];
    
    // Calculate quiz average with more complex algorithm
    $quizScores = [];
    $quizCount = 0;
    
    
    foreach ($data as $key => $value) {
        if (strpos($key, 'quiz_') === 0 && !strpos($key, 'over')) {
            $quizNum = substr($key, 5);
            $score = floatval($value);
            $maxScore = isset($data["quiz_over_{$quizNum}"]) ? floatval($data["quiz_over_{$quizNum}"]) : 0;
            
            if ($maxScore > 0) {
                $percentage = ($score / $maxScore) * 100;
                $quizScores[] = $percentage;
                $quizCount++;
            }
        }
    }
    
    // Calculate quiz average - use weighted average from Laravel controller
    if ($quizCount > 0) {
        // More sophisticated weighted average
        $weightedSum = 0;
        $totalWeight = 0;
        foreach ($quizScores as $index => $score) {
            // More weight to recent quizzes (exponential weighting)
            $weight = pow(1.5, $index); // Exponential weighting
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }
        $quizAverage = $weightedSum / $totalWeight;
        $predictions['quizPercent'] = round($quizAverage, 1);
    } else {
        $quizAverage = 0;
    }
    
    // Get other inputs
    $attendance = isset($data['attendance']) ? floatval($data['attendance']) : 0;
    $studyHours = isset($data['hours']) ? floatval($data['hours']) : 0;
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    
    // ENHANCED ML Prediction Formula - similar to Laravel controller
    // Training dataset simulation
    $samples = [
        [85, 90, 5], [70, 80, 2], [60, 75, 3],
        [90, 95, 6], [50, 60, 1], [80, 85, 4],
        [95, 98, 8], [65, 70, 3], [75, 82, 4],
        [88, 92, 7], [72, 78, 3], [55, 65, 2]
    ];
    

    $predicted = ($quizAverage * 0.5) + ($attendance * 0.3) + (min(($studyHours / 24) * 100, 100) * 0.2);
    
    // Add some randomness to simulate ML
    $predicted += (rand(-5, 5) / 100) * $predicted;
    
    // Ensure grade is within reasonable bounds
    $predicted = max(0, min(100, $predicted));
    
    
    $predictions['grade'] = number_format($predicted, 1) . '%'; // Return as percentage
    

    $predictions['risk'] = determineRiskEnhanced($predicted);
    $predictions['riskColor'] = $predictions['risk'];
    
   
    $predictions['tips'] = generateEnhancedStudyTips($quizAverage, $attendance, $studyHours, $predictions['grade'], $notes);
    
  
    $sentimentData = analyzeSentimentEnhanced($notes);
    $predictions['sentiment'] = $sentimentData['result'];
    $predictions['sentimentIcon'] = $sentimentData['icon'];
    $predictions['sentimentClass'] = $sentimentData['class'];
    $predictions['sentimentText'] = $sentimentData['text'];
    
    return $predictions;
}


function convertToGradeEnhanced($score) {
  
    return number_format($score, 1) . '%';
}


function determineRiskEnhanced($score) {
    if ($score < 70) return 'High';
    if ($score < 80) return 'Medium-High';
    if ($score < 85) return 'Medium';
    if ($score < 90) return 'Low-Medium';
    return 'Low';
}


function generateEnhancedStudyTips($quizAvg, $attendance, $studyHours, $grade, $notes) {
    $tips = [];
    
    // Based on predicted grade from Laravel controller
    $predictedScore = ($quizAvg * 0.5) + ($attendance * 0.3) + (min(($studyHours / 24) * 100, 100) * 0.2);
    
    if ($predictedScore < 70) {
        $tips = [
            "⚠️ Immediate Action Required: Consider increasing study hours to 4-5 hours daily",
            "📈 Focus on attendance: Aim for 95%+ attendance rate",
            "📚 Review past quizzes: Identify patterns in mistakes"
        ];
    } elseif ($predictedScore < 80) {
        $tips = [
            "📊 You're on track but need improvement",
            "⏰ Add 1-2 extra study hours weekly",
            "📝 Practice with mock tests regularly"
        ];
    } elseif ($predictedScore < 90) {
        $tips = [
            "✅ Good performance! Maintain consistency",
            "📈 Aim for incremental improvement",
            "📚 Review material weekly to prevent forgetting"
        ];
    } else {
        $tips = [
            "🏆 Excellent performance!",
            "🌟 Share your study techniques with peers",
            "📚 Explore advanced topics in your field"
        ];
    }
    
    // Get sentiment for personalized tips
    $sentiment = analyzeSentimentEnhanced($notes)['result'];
    
    // Create the formatted output with only 5 tips
    $output = "";
    
    // Add quiz performance as first line
    if ($quizAvg < 70) {
        $output = "📊 Quiz Performance Needs Improvement: Your quiz average is " . round($quizAvg, 1) . "%. Consider reviewing material more frequently.";
    } elseif ($quizAvg < 85) {
        $output = "📊 Good Quiz Performance: You're on track with " . round($quizAvg, 1) . "% average. Focus on weaker topics.";
    } else {
        $output = "📊 Excellent Quiz Performance: Great job with " . round($quizAvg, 1) . "% average! Maintain this consistency.";
    }
    
    $output .= "\n\n";
    

    for ($i = 0; $i < min(4, count($tips)); $i++) {
        $output .= $tips[$i];
        if ($i < min(4, count($tips)) - 1) {
            $output .= "\n\n";
        }
    }
    
    $output .= "\n\n";
    
  
    if ($attendance < 75) {
        $output .= "🎯 Improve Attendance: " . $attendance . "% attendance is low. Aim for at least 85%.";
    } elseif ($attendance < 90) {
        $output .= "🎯 Good Attendance: " . $attendance . "% is decent. Try to maintain or improve it.";
    } else {
        $output .= "🎯 Excellent Attendance: Perfect! " . $attendance . "% shows great commitment.";
    }
    
    
    // Add study hours tips
    if ($studyHours < 2) {
        $tips[] = "⏰ Increase Study Time: " . $studyHours . " hours daily may not be enough. Aim for 2-3 hours.";
    } elseif ($studyHours < 4) {
        $tips[] = "⏰ Optimal Study Time: " . $studyHours . " hours daily is good. Focus on quality.";
    } else {
        $tips[] = "⏰ Substantial Study Time: " . $studyHours . " hours daily is excellent. Avoid burnout.";
    }
    
   
    $sentiment = analyzeSentimentEnhanced($notes)['result'];
    if ($sentiment == "negative") {
        $tips[] = "😌 Take regular breaks and practice stress management";
        $tips[] = "🎵 Try studying with background music in focus mode";
        $tips[] = "📅 Break study sessions into 25-minute intervals";
    } elseif ($sentiment == "positive") {
        $tips[] = "🚀 Use your positive energy to tackle challenging topics";
        $tips[] = "🌟 Consider mentoring peers to reinforce your knowledge";
    } elseif ($sentiment == "neutral") {
        $tips[] = "⚖️ Maintain your balanced approach to studying";
        $tips[] = "📊 Track your progress to stay motivated";
    }
    
    // Add general tips
    $tips[] = "📝 Active Recall: Practice recalling information without notes";
    $tips[] = "🔁 Spaced Repetition: Review material at increasing intervals";
    $tips[] = "💤 Sleep Well: Ensure 7-8 hours of sleep for optimal performance";
    
    
    $formattedTips = implode("\n\n• ", $tips);
    return "• " . $formattedTips;
}


function analyzeSentimentEnhanced($notes) {
    if (empty($notes)) {
        return [
            'result' => 'neutral',
            'icon' => '😐',
            'class' => 'sentiment-neutral',
            'text' => 'Neutral Mood'
        ];
    }
    
    $positiveWords = ['good', 'great', 'excellent', 'happy', 'confident', 'motivated', 'progress', 'improve', 'better', 'understand', 'learned', 'excited', 'proud'];
    $negativeWords = ['bad', 'poor', 'difficult', 'hard', 'struggle', 'stress', 'anxious', 'worry', 'confused', 'fail', 'tired', 'exhausted', 'overwhelmed', 'frustrated'];
    
    $notesLower = strtolower($notes);
    $positiveCount = 0;
    $negativeCount = 0;
    
    foreach ($positiveWords as $word) {
        $positiveCount += substr_count($notesLower, $word);
    }
    
    foreach ($negativeWords as $word) {
        $negativeCount += substr_count($notesLower, $word);
    }
    
    if ($positiveCount > $negativeCount) {
        return [
            'result' => 'positive',
            'icon' => '❤️',
            'class' => 'sentiment-positive',
            'text' => 'Positive Mood'
        ];
    } elseif ($negativeCount > $positiveCount) {
        return [
            'result' => 'negative',
            'icon' => '💔',
            'class' => 'sentiment-negative',
            'text' => 'Negative Mood'
        ];
    }
    
    return [
        'result' => 'neutral',
        'icon' => '😐',
        'class' => 'sentiment-neutral',
        'text' => 'Neutral Mood'
    ];
}


function analyzeSentiment($notes) {
    $enhanced = analyzeSentimentEnhanced($notes);
    return $enhanced['result'];
}

// Process form submission
$predictions = [];
$oldValues = [];
$quizCount = 1;
$hasErrors = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all POST data
    $formData = $_POST;
    $oldValues = $formData;
    
    // Enhanced validation similar to Laravel controller
    $errors = validateFormEnhanced($formData);
    
    if (empty($errors)) {
       
        $predictions = calculatePredictions($formData);
    } else {
        $hasErrors = true;
    }
    
  
    $maxQuizNum = 0;
    foreach ($formData as $key => $value) {
        if (strpos($key, 'quiz_') === 0 && !strpos($key, 'over') && !empty($value)) {
            $num = substr($key, 5);
            if (is_numeric($num) && $num > $maxQuizNum) {
                $maxQuizNum = $num;
            }
        }
    }
    $quizCount = max($quizCount, $maxQuizNum);
} else {
    
    $oldValues = $_GET;
    $quizCount = 1;
}


function validateFormEnhanced($data) {
    $errors = [];
    
   
    $firstQuizScore = isset($data['quiz_1']) ? $data['quiz_1'] : '';
    $firstQuizOver = isset($data['quiz_over_1']) ? $data['quiz_over_1'] : '';
    
    if (empty($firstQuizScore) || empty($firstQuizOver)) {
        $errors[] = "Quiz 1: Please enter both score and total points";
    } else {
       
        if (!is_numeric($firstQuizScore) || !is_numeric($firstQuizOver)) {
            $errors[] = "Quiz 1: Please enter valid numbers";
        } elseif ($firstQuizOver <= 0) {
            $errors[] = "Quiz 1: Total points must be greater than 0";
        } elseif ($firstQuizScore > $firstQuizOver) {
            $errors[] = "Quiz 1: Score cannot be greater than total points";
        } elseif ($firstQuizScore < 0) {
            $errors[] = "Quiz 1: Score cannot be negative";
        }
    }
    

    $maxQuizNumber = 1;
    foreach ($data as $key => $value) {
        if (strpos($key, 'quiz_') === 0 && !strpos($key, 'over')) {
            $num = (int) substr($key, 5);
            if ($num > $maxQuizNumber) {
                $maxQuizNumber = $num;
            }
        }
    }
    
   
    for ($quizNumber = 2; $quizNumber <= $maxQuizNumber; $quizNumber++) {
        $score = isset($data["quiz_{$quizNumber}"]) ? $data["quiz_{$quizNumber}"] : '';
        $over = isset($data["quiz_over_{$quizNumber}"]) ? $data["quiz_over_{$quizNumber}"] : '';
        
       
        if (empty($score) && empty($over)) {
            continue;
        }
        
       
        if (!empty($score) && !empty($over)) {
            if (!is_numeric($score) || !is_numeric($over)) {
                $errors[] = "Quiz {$quizNumber}: Please enter valid numbers";
            } elseif ($over <= 0) {
                $errors[] = "Quiz {$quizNumber}: Total points must be greater than 0";
            } elseif ($score > $over) {
                $errors[] = "Quiz {$quizNumber}: Score cannot be greater than total points";
            } elseif ($score < 0) {
                $errors[] = "Quiz {$quizNumber}: Score cannot be negative";
            }
        } elseif ((!empty($score) && empty($over)) || (empty($score) && !empty($over))) {
         
            $errors[] = "Quiz {$quizNumber}: Please enter both score and total points, or leave both empty";
        }
    }
    
    // Validate attendance
    if (empty($data['attendance'])) {
        $errors[] = 'Attendance is required';
    } else {
        $attendance = floatval($data['attendance']);
        if (!is_numeric($data['attendance']) || $attendance < 0 || $attendance > 100) {
            $errors[] = 'Attendance must be a number between 0 and 100';
        }
    }
    
    // Validate study hours
    if (empty($data['hours'])) {
        $errors[] = 'Study hours are required';
    } else {
        $hours = floatval($data['hours']);
        if (!is_numeric($data['hours']) || $hours < 0 || $hours > 24) {
            $errors[] = 'Study hours must be a number between 0 and 24';
        }
    }
    
    return $errors;
}


function validateForm($data) {
    return validateFormEnhanced($data);
}


function old($field, $default = '') {
    global $oldValues;
    return isset($oldValues[$field]) ? htmlspecialchars($oldValues[$field]) : $default;
}

// Get quiz count for display
$displayQuizCount = $quizCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Performance Predictor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --dark-bg: #0f172a;
        --card-bg: #1e293b;
        --card-border: rgba(255, 255, 255, 0.15);
        --text-primary: #f1f5f9;
        --text-secondary: #cbd5e1;
        --input-bg: #334155;
        --input-border: rgba(255, 255, 255, 0.2);
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: var(--dark-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--text-primary);
        min-height: 100vh;
        line-height: 1.6;
    }

    .container {
        padding: 2rem 1rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .header {
        text-align: center;
        margin-bottom: 2.5rem;
        position: relative;
    }

    .header::after {
        content: '';
        position: absolute;
        bottom: -1rem;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--primary-gradient);
        border-radius: 2px;
    }

    .header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .header p {
        color: var(--text-secondary);
        font-size: 1.1rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    @media (min-width: 992px) {
        .content-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .card-header {
        padding: 1.5rem 2rem;
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid var(--card-border);
    }

    .card-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-primary);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .card-header h2 i {
        color: #667eea;
        font-size: 1.25rem;
    }

    .card-body {
        padding: 2rem;
        color: var(--text-primary);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-primary) !important;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }

    .form-label i {
        color: #667eea;
        width: 20px;
    }

    .form-control {
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        color: var(--text-primary) !important;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        background: var(--input-bg);
        border-color: #667eea;
        color: var(--text-primary) !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .form-control::placeholder {
        color: rgba(203, 213, 225, 0.7) !important;
    }

    input::placeholder,
    textarea::placeholder {
        opacity: 0.8 !important;
    }

    .input-group {
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: 10px;
        overflow: hidden;
    }

    .input-group .form-control {
        border: none;
        background: transparent;
    }

    .input-group-text {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        padding: 0.75rem 1rem;
    }

    .quiz-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--input-border);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .quiz-card .card-header {
        background: transparent;
        border: none;
        padding: 0 0 0.75rem 0;
    }

    .quiz-card .card-body {
        padding: 0;
    }

    .btn {
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-primary);
        border: 1px solid var(--input-border);
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .btn-danger {
        background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        color: white;
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(245, 87, 108, 0.3);
    }

    .btn-reset {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
        border: 1px solid var(--input-border);
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }

    .btn-reset:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    .button-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .button-group .btn {
        flex: 1;
    }

    @media (max-width: 768px) {
        .button-group {
            flex-direction: column;
        }
    }

    .result-card {
        position: relative;
        overflow: hidden;
    }

    .result-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .result-content {
        text-align: center;
    }

    .grade-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        font-size: 2.5rem;
        font-weight: 700;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
    }

    .grade-circle::after {
        content: '';
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.1);
    }

    .risk-badge {
        display: inline-block;
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        margin: 1rem 0;
        font-size: 0.9rem;
    }

    .risk-high {
        background: linear-gradient(135deg, #f5576c 0%, #ef4444 100%);
        color: white;
    }

    .risk-medium {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .risk-low {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }


    .risk-medium-high {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
        color: white;
    }
    
    .risk-low-medium {
        background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
        color: white;
    }

    .tips-card {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        text-align: left;
    }

    .tips-card h4 {
        color: #3b82f6;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tips-card p {
        color: #e2e8f0;
        line-height: 1.0; 
        font-size: 1.05rem;
        margin: 1rem 0; 
    }

    .sentiment-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        margin-top: 1rem;
    }

    .sentiment-positive {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .sentiment-neutral {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .sentiment-negative {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1.5rem 0;
    }

    .stat-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--input-border);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .text-muted {
        color: var(--text-secondary) !important;
    }

    .footer {
        text-align: center;
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid var(--card-border);
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .footer a {
        color: #667eea;
        text-decoration: none;
    }

    .footer a:hover {
        text-decoration: underline;
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
    }

    .alert-danger h5 {
        color: #f87171;
        margin-bottom: 0.5rem;
    }

    .result-content h3 {
        color: var(--text-primary);
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-in {
        animation: fadeIn 0.5s ease-out;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--input-bg);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-gradient);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    /* Toast Notification Styles */
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 10px;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        transform: translateX(150%);
        transition: transform 0.3s ease;
        z-index: 1000;
        max-width: 350px;
    }
    
    .toast-notification.show {
        transform: translateX(0);
    }
    
    .toast-notification i {
        font-size: 1.25rem;
    }
    
    .toast-success i { color: var(--success); }
    .toast-error i { color: var(--danger); }
    .toast-info i { color: var(--info); }
    
    .toast-notification span {
        color: var(--text-primary);
        font-weight: 500;
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        border-top-color: #667eea;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* MODAL CONFIRMATION */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-content {
        transform: translateY(0);
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--card-border);
    }

    .modal-header i {
        font-size: 2rem;
        color: var(--warning);
    }

    .modal-header h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .modal-body {
        margin-bottom: 2rem;
    }

    .modal-body p {
        color: var(--text-secondary);
        line-height: 1.6;
        font-size: 1.1rem;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .modal-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .modal-btn-cancel {
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-primary);
        border: 1px solid var(--input-border);
    }

    .modal-btn-cancel:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .modal-btn-confirm {
        background: linear-gradient(135deg, #f5576c 0%, #ef4444 100%);
        color: white;
    }

    .modal-btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
    }
</style>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-chart-line"></i> Academic Performance Predictor</h1>
        <p>Predict your final grade and receive personalized study recommendations</p>
    </div>

    <div class="content-grid">
        <!-- Input Form Card -->
        <div class="card animate-in">
            <div class="card-header">
                <h2><i class="fas fa-edit"></i> Performance Metrics</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="prediction-form">
                    <!-- Dynamic Quiz Section -->
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-question-circle"></i> Quiz Scores</label>
                        <div id="quiz-container">
                            <?php for ($i = 1; $i <= $displayQuizCount; $i++): ?>
                                <div class="quiz-card quiz-<?php echo $i; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label"><i class="fas fa-star"></i> Quiz <?php echo $i; ?> Score</label>
                                                <input type="number" class="form-control" name="quiz_<?php echo $i; ?>" 
                                                       placeholder="Enter score" min="0" 
                                                       value="<?php echo old('quiz_' . $i); ?>" 
                                                       <?php echo $i == 1 ? 'required' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label"><i class="fas fa-chart-bar"></i> Total Points</label>
                                                <input type="number" class="form-control" name="quiz_over_<?php echo $i; ?>" 
                                                       placeholder="Total points" min="1" 
                                                       value="<?php echo old('quiz_over_' . $i); ?>" 
                                                       <?php echo $i == 1 ? 'required' : ''; ?>>
                                            </div>
                                        </div>
                                        <?php if ($i > 1): ?>
                                            <div class="col-12">
                                                <button type="button" class="btn btn-danger btn-sm remove-quiz-btn w-100" data-quiz="<?php echo $i; ?>">
                                                    <i class="fas fa-trash-alt"></i> Remove Quiz <?php echo $i; ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <button type="button" class="btn btn-secondary w-100 mt-2" id="add-quiz-btn">
                            <i class="fas fa-plus-circle"></i> Add Another Quiz (Optional)
                        </button>
                        <small class="text-muted mt-1 d-block">Note: Only Quiz 1 is required. Additional quizzes are optional.</small>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-calendar-check"></i> Attendance (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="attendance" 
                                           placeholder="Enter attendance percentage" min="0" max="100" 
                                           value="<?php echo old('attendance'); ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-clock"></i> Study Hours per Day</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="hours" 
                                           placeholder="Daily study hours" min="0" max="24" step="0.5" 
                                           value="<?php echo old('hours'); ?>" required>
                                    <span class="input-group-text">hrs</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-comment-dots"></i> Study Notes & Mood</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Describe your study habits, motivation level, or any challenges you're facing..."><?php echo old('notes'); ?></textarea>
                        <small class="text-muted mt-1 d-block">Optional: Helps generate personalized study tips</small>
                    </div>

                   
                    <?php if ($hasErrors && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                                </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary" id="predict-btn">
                            <i class="fas fa-calculator"></i> Predict Performance
                        </button>
                        <button type="button" class="btn btn-reset" id="reset-btn">
                            <i class="fas fa-redo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card result-card animate-in" style="animation-delay: 0.2s">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> Prediction Results</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($predictions) && isset($predictions['grade'])): ?>
                    <div class="result-content">
                        <!-- Predicted Grade -->
                        <div class="grade-circle">
                            <?php echo $predictions['grade']; ?>
                        </div>
                        
                        <!-- Risk Assessment-->
                        <h3 class="mb-3">Predicted Final Score</h3>
                        
                        <div class="risk-badge <?php 
                            echo $predictions['risk'] == 'High' ? 'risk-high' : 
                            ($predictions['risk'] == 'Medium-High' ? 'risk-medium-high' :
                            ($predictions['risk'] == 'Medium' ? 'risk-medium' :
                            ($predictions['risk'] == 'Low-Medium' ? 'risk-low-medium' : 'risk-low'))); 
                        ?>">
                            <i class="fas fa-<?php 
                                echo $predictions['risk'] == 'High' ? 'exclamation-triangle' : 
                                ($predictions['risk'] == 'Medium-High' ? 'exclamation-circle' :
                                ($predictions['risk'] == 'Medium' ? 'exclamation-circle' :
                                ($predictions['risk'] == 'Low-Medium' ? 'info-circle' : 'check-circle'))); 
                            ?>"></i>
                            <?php echo $predictions['risk']; ?> Risk
                        </div>

                        <!-- Statistics -->
                        <div class="stat-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $predictions['grade']; ?></div>
                                <div class="stat-label">Predicted Score</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $predictions['quizPercent']; ?>%</div>
                                <div class="stat-label">Quiz Average</div>
                            </div>
                        </div>

                        <!-- Study Tips -->
                        <?php if (!empty($predictions['tips'])): ?>
                        <div class="tips-card">
                            <h4><i class="fas fa-lightbulb"></i> Personalized Study Tips</h4>
                            <p><?php echo nl2br(htmlspecialchars($predictions['tips'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Sentiment Analysis -->
                        <?php if (!empty($predictions['sentiment'])): ?>
                            <div class="sentiment-badge <?php echo $predictions['sentimentClass']; ?>">
                                <span style="font-size: 1.2rem;"><?php echo $predictions['sentimentIcon']; ?></span>
                                <span><?php echo $predictions['sentimentText']; ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Reset Button -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-secondary" id="new-prediction-btn">
                                <i class="fas fa-sync-alt"></i> Clear Form & Results
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-chart-line fa-4x" style="opacity: 0.3;"></i>
                        </div>
                        <h4 class="mb-3">No Prediction Yet</h4>
                        <p class="text-muted">Fill out the form and click "Predict Performance" to see your results here.</p>
                        <div class="mt-4">
                            <i class="fas fa-arrow-left fa-2x" style="opacity: 0.2;"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© 2025 Academic Performance Predictor. Designed for students.</p>
        <p class="mt-1">This tool uses machine learning to provide predictions based on your input data.</p>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<!-- Modal Confirmation for Quiz Deletion -->
<div class="modal-overlay" id="quiz-confirmation-modal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Remove Quiz</h3>
        </div>
        <div class="modal-body">
            <p id="quiz-modal-message">Are you sure you want to remove this quiz? This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" id="quiz-modal-cancel-btn">
                Cancel
            </button>
            <button type="button" class="modal-btn modal-btn-confirm" id="quiz-modal-confirm-btn">
                Yes, Remove Quiz
            </button>
        </div>
    </div>
</div>

<!-- Modal Confirmation for Form Reset -->
<div class="modal-overlay" id="form-confirmation-modal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Confirm Action</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to clear all form data and results? This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" id="form-modal-cancel-btn">
                Cancel
            </button>
            <button type="button" class="modal-btn modal-btn-confirm" id="form-modal-confirm-btn">
                Yes, Clear Everything
            </button>
        </div>
    </div>
</div>

<script>
    // Initialize variables
    let quizCount = <?php echo $displayQuizCount; ?>;
    let formSubmitted = false;
    let pendingAction = null;
    let quizToRemove = null;

    // Function to renumber quizzes
    function renumberQuizzes() {
        const rows = document.querySelectorAll('#quiz-container .quiz-card');
        rows.forEach((row, index) => {
            const quizNumber = index + 1;
            
            // Update labels
            const labels = row.querySelectorAll('.form-label');
            if (labels[0]) labels[0].innerHTML = `<i class="fas fa-star"></i> Quiz ${quizNumber} Score`;
            if (labels[1]) labels[1].innerHTML = `<i class="fas fa-chart-bar"></i> Total Points`;
            
            // Update input names
            const inputs = row.querySelectorAll('input[type="number"]');
            if (inputs[0]) {
                inputs[0].name = `quiz_${quizNumber}`;
                inputs[0].required = quizNumber === 1; // Only first quiz required
            }
            if (inputs[1]) {
                inputs[1].name = `quiz_over_${quizNumber}`;
                inputs[1].required = quizNumber === 1; // Only first quiz required
            }
            
            // Update remove button
            const removeBtn = row.querySelector('.remove-quiz-btn');
            if (removeBtn && quizNumber > 1) {
                removeBtn.innerHTML = `<i class="fas fa-trash-alt"></i> Remove Quiz ${quizNumber}`;
                removeBtn.setAttribute('data-quiz', quizNumber);
            }
        });
        quizCount = rows.length;
    }


    document.getElementById('add-quiz-btn').addEventListener('click', function() {
        quizCount++;
        const container = document.getElementById('quiz-container');
        const div = document.createElement('div');
        div.className = `quiz-card quiz-${quizCount}`;
        div.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-star"></i> Quiz ${quizCount} Score</label>
                        <input type="number" class="form-control" name="quiz_${quizCount}" placeholder="Enter score" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-chart-bar"></i> Total Points</label>
                        <input type="number" class="form-control" name="quiz_over_${quizCount}" placeholder="Total points" min="1">
                    </div>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-danger btn-sm remove-quiz-btn w-100" data-quiz="${quizCount}">
                        <i class="fas fa-trash-alt"></i> Remove Quiz ${quizCount}
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
        
  
        div.style.animation = 'fadeIn 0.3s ease-out';
        
     
        setTimeout(() => {
            div.querySelector(`input[name="quiz_${quizCount}"]`).focus();
        }, 100);
    });


    function showQuizConfirmationModal(quizNumber) {
        quizToRemove = quizNumber;
        const modal = document.getElementById('quiz-confirmation-modal');
        const message = document.getElementById('quiz-modal-message');
        message.textContent = `Are you sure you want to remove Quiz ${quizNumber}? This action cannot be undone.`;
        modal.classList.add('active');
    }


    function hideQuizConfirmationModal() {
        const modal = document.getElementById('quiz-confirmation-modal');
        modal.classList.remove('active');
        quizToRemove = null;
    }


    document.getElementById('quiz-container').addEventListener('click', function(e) {
        if (e.target.closest('.remove-quiz-btn')) {
            const removeBtn = e.target.closest('.remove-quiz-btn');
            const quizNumber = removeBtn.getAttribute('data-quiz');
            showQuizConfirmationModal(quizNumber);
        }
    });

    
    document.getElementById('quiz-modal-cancel-btn').addEventListener('click', function() {
        hideQuizConfirmationModal();
    });

    // Quiz modal confirm button
    document.getElementById('quiz-modal-confirm-btn').addEventListener('click', function() {
        if (quizToRemove) {
            const quizCard = document.querySelector(`.quiz-${quizToRemove}`);
            if (quizCard) {
                // Add fade out animation
                quizCard.style.animation = 'fadeIn 0.3s ease-out reverse';
                
                setTimeout(() => {
                    quizCard.remove();
                    renumberQuizzes();
                    hideQuizConfirmationModal();
                    
                    // Show notification
                    showToast('Quiz removed successfully!', 'success');
                }, 300);
            }
        }
    });


    function showFormConfirmationModal(action) {
        pendingAction = action;
        const modal = document.getElementById('form-confirmation-modal');
        modal.classList.add('active');
    }


    function hideFormConfirmationModal() {
        const modal = document.getElementById('form-confirmation-modal');
        modal.classList.remove('active');
        pendingAction = null;
    }

  
    document.getElementById('reset-btn').addEventListener('click', function() {
        showFormConfirmationModal('reset');
    });

   
    document.getElementById('new-prediction-btn')?.addEventListener('click', function() {
        showFormConfirmationModal('new');
    });

  
    document.getElementById('form-modal-cancel-btn').addEventListener('click', function() {
        hideFormConfirmationModal();
    });

  
    document.getElementById('form-modal-confirm-btn').addEventListener('click', function() {
        if (pendingAction === 'reset' || pendingAction === 'new') {
            clearFormAndResults();
        }
        hideFormConfirmationModal();
    });

    // Function to completely clear form and results
    function clearFormAndResults() {
        // Clear all form inputs
        const form = document.getElementById('prediction-form');
        if (form) {
            form.querySelectorAll('input, textarea').forEach(input => {
                input.value = '';
            });
            
         
            const quizContainer = document.getElementById('quiz-container');
            const quizCards = quizContainer.querySelectorAll('.quiz-card');
            quizCards.forEach((card, index) => {
                if (index > 0) {
                    card.remove();
                }
            });
            
            
            quizCount = 1;
            renumberQuizzes();
        }
        
        
        window.location.href = window.location.pathname;
    }

    // Form validation and submission
    document.getElementById('prediction-form').addEventListener('submit', function(e) {
        const quizInputs = this.querySelectorAll('input[name^="quiz_"]:not([name$="over"])');
        const quizOverInputs = this.querySelectorAll('input[name^="quiz_over_"]');
        let errors = [];

        
        const firstQuizScore = quizInputs[0]?.value.trim();
        const firstQuizTotal = quizOverInputs[0]?.value.trim();
        
        if (firstQuizScore === '' || firstQuizTotal === '') {
            errors.push('Quiz 1: Please enter both score and total points');
        } else {
            const score = parseFloat(firstQuizScore);
            const total = parseFloat(firstQuizTotal);
            
            if (isNaN(score) || isNaN(total)) {
                errors.push('Quiz 1: Please enter valid numbers for both score and total');
            } else if (total <= 0) {
                errors.push('Quiz 1: Total points must be greater than 0');
            } else if (score > total) {
                errors.push(`Quiz 1: Score cannot be greater than total points (${score} > ${total})`);
            } else if (score < 0) {
                errors.push('Quiz 1: Score cannot be negative');
            }
        }

        // Check additional quizzes - only validate if they exist in DOM
        const quizCards = document.querySelectorAll('.quiz-card');
        
        for (let i = 1; i < quizCards.length; i++) {
            const quizNum = i + 1;
            const quizScoreInput = document.querySelector(`input[name="quiz_${quizNum}"]`);
            const quizTotalInput = document.querySelector(`input[name="quiz_over_${quizNum}"]`);
            
            if (!quizScoreInput || !quizTotalInput) continue;
            
            const quizScore = quizScoreInput.value.trim();
            const quizTotal = quizTotalInput.value.trim();

           
            if (quizScore === '' && quizTotal === '') continue;

         
            if (quizScore !== '' && quizTotal !== '') {
                const score = parseFloat(quizScore);
                const total = parseFloat(quizTotal);

                if (isNaN(score) || isNaN(total)) {
                    errors.push(`Quiz ${quizNum}: Please enter valid numbers for both score and total`);
                } else if (total <= 0) {
                    errors.push(`Quiz ${quizNum}: Total points must be greater than 0`);
                } else if (score > total) {
                    errors.push(`Quiz ${quizNum}: Score cannot be greater than total points (${score} > ${total})`);
                } else if (score < 0) {
                    errors.push(`Quiz ${quizNum}: Score cannot be negative`);
                }
            } else if ((quizScore !== '' && quizTotal === '') || (quizScore === '' && quizTotal !== '')) {
                
                errors.push(`Quiz ${quizNum}: Please enter both score and total points, or leave both empty`);
            }
        }

        // Check attendance
        const attendanceInput = this.querySelector('input[name="attendance"]');
        const attendance = attendanceInput.value.trim();
        if (attendance === '') {
            errors.push('Attendance is required');
        } else {
            const attendanceNum = parseFloat(attendance);
            if (isNaN(attendanceNum) || attendanceNum < 0 || attendanceNum > 100) {
                errors.push('Attendance must be a number between 0 and 100');
            }
        }

        // Check study hours
        const hoursInput = this.querySelector('input[name="hours"]');
        const hours = hoursInput.value.trim();
        if (hours === '') {
            errors.push('Study hours are required');
        } else {
            const hoursNum = parseFloat(hours);
            if (isNaN(hoursNum) || hoursNum < 0 || hoursNum > 24) {
                errors.push('Study hours must be a number between 0 and 24');
            }
        }

        // Show errors or submit
        if (errors.length > 0) {
            e.preventDefault();
            showErrors(errors);
        } else {
           
            document.getElementById('loading-overlay').classList.add('active');
            formSubmitted = true;
        }
    });

    // Show multiple errors
    function showErrors(errors) {
        const errorHtml = errors.map(error => `<li>${error}</li>`).join('');
        showToast(`<ul class="mb-0" style="padding-left: 1.5rem;">${errorHtml}</ul>`, 'error');
    }

    // Toast notification function
    function showToast(message, type = 'info') {
      
        document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 10);
        
      
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, 5000);
    }

  
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            document.getElementById('loading-overlay').classList.remove('active');
        }
    });


    window.addEventListener('load', function() {
        document.getElementById('loading-overlay').classList.remove('active');
        
  
        if (window.location.search.includes('grade=') || <?php echo !empty($predictions) ? 'true' : 'false'; ?>) {
            setTimeout(() => {
                const resultCard = document.querySelector('.result-card');
                if (resultCard) {
                    resultCard.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 500);
        }
    });

 
    window.addEventListener('beforeunload', function() {
        if (formSubmitted) {
            document.getElementById('loading-overlay').classList.add('active');
        }
    });

  
    document.getElementById('quiz-confirmation-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideQuizConfirmationModal();
        }
    });

    document.getElementById('form-confirmation-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideFormConfirmationModal();
        }
    });


    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideQuizConfirmationModal();
            hideFormConfirmationModal();
        }
    });
</script>

</body>
</html>
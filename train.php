<?php
require 'vendor/autoload.php';

use Phpml\Regression\LeastSquares;
use Phpml\ModelManager;

// Training samples: [quiz score, attendance %, study hours]
$samples = [
    [80, 90, 2],
    [70, 80, 1],
    [90, 95, 3],
    [60, 70, 0.5],
    [85, 88, 2.5],
    [50, 60, 1],
];

// Labels: final grade
$labels = [85, 78, 92, 65, 89, 60];

// Train model
$regression = new LeastSquares();
$regression->train($samples, $labels);

// Save model
$modelManager = new ModelManager();
$modelManager->saveToFile($regression, 'model.zip');

echo "Model trained successfully!";

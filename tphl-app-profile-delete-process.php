<?php
$steps = [
    [
        "title" => "Step-1",
        "text"  => 'Open the <strong>TPHL App</strong> and tap the circular button located at the center of the bottom navigation bar.',
        "img"   => "assets/delete-request/step-1.png"
    ],
    [
        "title" => "Step - 2",
        "text"  => 'If you are not signed in, please sign in first and then tap the circular button again. You will be taken to the <strong>Dashboard</strong>.',
        "img"   => "assets/delete-request/step-2.png"
    ],
    [
        "title" => "Step - 3",
        "text"  => 'From there, tap the <strong>top-right menu icon</strong> to open the navigation drawer.',
        "img"   => "assets/delete-request/step-3.png"
    ],
    [
        "title" => "Step - 4",
        "text"  => 'Select <strong>My Profile</strong>, which will take you to your profile page. On the profile page, you will find the <strong>Delete Profile</strong> option.',
        "img"   => "assets/delete-request/step-4.jpeg"
    ],
    [
        "title" => "Step - 5",
        "text"  => 'When you tap on it, a warning message will appear asking, <em>"Are you sure you want to delete your account?"</em> You will have two choices: <strong>Accept</strong> or <strong>Deny</strong>.',
        "img"   => "assets/delete-request/step-5.jpeg"
    ],
    [
        "title" => "Step - 6",
        "text"  => 'If you confirm the deletion, you will be logged out and your account deletion request will be submitted to the authority. You will be notified as soon as the authority approves and permanently deletes your account.',
        "img"   => "assets/delete-request/step-6.png"
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPHL App- Profile delete tutorial</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Source+Serif+4:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #fff;
            color: #202124;
        }
      .container{
          width:1300px;
          max-width:100%;
          margin:0 auto;
          
      }

        /* TOP BAR */
        .top-bar {
            background: #1a1a1a;
            color: #fff;
            padding: 0 20px;
            height: 44px;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
        }

        /* HERO */
        .hero {
            background: #1b5e20;
            background-image: linear-gradient(135deg, #1a4a1c 0%, #2e7d32 45%, #388e3c 75%, #1a4a1c 100%);
            padding: 60px 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                -45deg,
                rgba(0,0,0,.05) 0px,
                rgba(0,0,0,.05) 2px,
                transparent 2px,
                transparent 20px
            );
        }

        .hero-box {
            border: 3px solid rgba(255,255,255,.9);
            padding: 36px 60px;
            max-width: 700px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero-box h1 {
            font-family: 'Merriweather', Georgia, serif;
            font-size: clamp(26px, 4.5vw, 44px);
            color: #fff;
            line-height: 1.25;
            font-weight: 700;
        }

        /* OVERVIEW */
        .overview {
            max-width: 860px;
            margin: 48px auto;
            padding: 0 40px;
        }

        .overview ol {
            list-style: decimal;
            padding-left: 20px;
            font-size: 15px;
            line-height: 1.9;
            color: #333;
        }

        .overview ol li { margin-bottom: 10px; }

        /* STEPS */
        .step {
            max-width: 1300px;
            margin: 0 auto 70px;
            padding: 0 40px;
            display: flex;
            align-items: flex-start;
            gap: 52px;
        }

        .step-img {
            flex: 0 0 auto;
            width: 280px;
        }

        .step-img img {
            width: 100%;
            display: block;
            border-radius: 6px;
            box-shadow: 0 6px 28px rgba(0,0,0,.13);
        }

        .step-content {
            flex: 1;
        }

        .step-content h2 {
            font-family: 'Source Serif 4', Georgia, serif;
            font-size: 26px;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 16px;
        }

        .step-content p {
            font-size: 21px;
            line-height: 1.75;
            color: #202124;
            text-align: justify;
        }

        hr.divider {
            border: none;
            border-top: 1px solid #e8e8e8;
            max-width: 1000px;
            margin: 0 auto 70px;
        }

.flex{
    display:flex;
}
.justify-center{
    justify-content:center;
    margin-bottom:20px;
}

        @media (max-width: 680px) {
            .step { flex-direction: column; gap: 20px; padding: 0 20px; }
            .step-img { width: 100%; }
            .hero-box { padding: 24px 18px; }
            .overview { padding: 0 20px; }
        }
    </style>
</head>
<body>

<div class="top-bar">TPHL App- Profile delete tuto...</div>

<div class="hero">
    <div class="hero-box">
        <h1>How to delete your account on TPHL App</h1>
    </div>
</div>

<div class="overview">
    <ol>
        <li>Open the <strong>TPHL App</strong> and tap the circular button located at the center of the bottom navigation bar.<br>
            If you are not signed in, please sign in first and then tap the circular button again.</li>
        <li>You will be taken to the <strong>Dashboard</strong>. From there, tap the <strong>top-right menu icon</strong> to open the navigation drawer.<br>
            Select <strong>My Profile</strong>, which will take you to your profile page.</li>
        <li>On the profile page, you will find the <strong>Delete Account</strong> option.<br>
            When you tap on it, a warning message will appear asking, <em>"Are you sure you want to delete your account?"</em></li>
        <li>You will have two choices: <strong>Accept</strong> or <strong>Deny</strong>.<br>
            If you confirm the deletion, you will be logged out and your account deletion request will be submitted to the authority. You will be notified as soon as the authority approves and permanently deletes your account.</li>
    </ol>
</div>

<?php foreach ($steps as $i => $step): ?>
    <?php if ($i > 0): ?><hr class="divider"><?php endif; ?>
    <div class="step">
        <div class="step-img">
            <img src="<?= htmlspecialchars($step['img']) ?>" alt="<?= htmlspecialchars($step['title']) ?>">
        </div>
        <div class="step-content">
            <h2><?= htmlspecialchars($step['title']) ?></h2>
            <p><?= $step['text'] ?></p>
        </div>
    </div>
<?php endforeach; ?>
    <div class="container">
        <div class="flex justify-center">
            <img src="assets/delete-request/step-6.1.png">
        </div>
    </div>


</body>
</html>
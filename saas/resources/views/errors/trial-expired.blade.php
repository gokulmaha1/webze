<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Period Ended - Webze</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #fbbf24;
            --bg: #0f172a;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(251, 191, 36, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.05) 0%, transparent 40%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }

        .container {
            max-width: 500px;
            width: 90%;
            padding: 3rem;
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.3);
        }

        .icon-box svg {
            width: 40px;
            height: 40px;
            color: #000;
        }

        h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        p {
            font-size: 1.1rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .contact-card {
            background: rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px dashed var(--glass-border);
        }

        .phone {
            display: block;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            margin-top: 0.5rem;
        }

        .logo {
            margin-top: 3rem;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        
        <h1>Trial Period Ended</h1>
        <p>Your trial period visit has been ended. We hope you enjoyed the professional website experience!</p>
        
        <div class="contact-card">
            <span>Contact Webze Admin Team</span>
            <a href="tel:9629759769" class="phone">9629759769</a>
        </div>

        <div class="logo">Webze SaaS</div>
    </div>
</body>
</html>

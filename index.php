<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI & GPS Attendance System</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="responsive.css">
    <!-- Added Google Fonts link for Montserrat and Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="mobile_optimizations.js"></script>
    <style>
        /* Base styles to ensure content fits */
        body {
            margin: 0;
            padding: 0;
            background: black;
            color: white;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Additional styles for enhanced button and animations */
        .hero-section {
            position: relative;
            overflow: auto; /* Changed from 'hidden' to allow scrolling if needed */
            height: 100vh;
            max-height: 900px;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: black;
            padding: 20px 0;
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
        }

        @keyframes float {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-20vh) translateX(20px);
                opacity: 0;
            }
        }

        .business-btn {
            position: relative;
            padding: 18px 36px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 18px;
            letter-spacing: 1px;
            color: white;
            background: linear-gradient(135deg, #0072ff, #00c6ff);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(0, 114, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            z-index: 1;
        }

        .business-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background: linear-gradient(135deg, #005cbf, #0072ff);
            transition: width 0.4s ease;
            z-index: -1;
        }

        .business-btn:hover::before {
            width: 100%;
        }

        .business-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 114, 255, 0.4);
        }

        .business-btn:active {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 114, 255, 0.4);
        }

        .btn-icon {
            transition: transform 0.3s ease;
        }

        .business-btn:hover .btn-icon {
            transform: translateX(5px);
        }

        .btn-highlight {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.6s ease;
        }

        .business-btn:hover .btn-highlight {
            opacity: 1;
        }

        /* Pulse animation for attention */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 114, 255, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(0, 114, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 114, 255, 0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        /* Enhanced text styles */
        .subtitle {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 30px;
            color: #f0f0f0;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1s forwards 0.5s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Graphics surrounding the button */
        .button-graphics {
            position: relative;
            display: inline-block;
            margin-top: 10px;
        }
        
        .button-graphics::before, 
        .button-graphics::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 50px;
            border: 2px solid rgba(0, 114, 255, 0.5);
            border-radius: 50%;
            animation: expand 2s infinite alternate;
            pointer-events: none;
        }
        
        .button-graphics::before {
            top: -20px;
            left: -20px;
            animation-delay: 0.5s;
        }
        
        .button-graphics::after {
            bottom: -20px;
            right: -20px;
        }
        
        @keyframes expand {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        
        /* Ripple effect for click animation */
        .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: rippleEffect 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes rippleEffect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        /* Responsive styles for the business button */
        @media (max-width: 768px) {
            .subtitle {
                font-size: 1.2rem;
                margin-bottom: 30px;
            }
            
            .business-btn {
                padding: 16px 28px;
                font-size: 16px;
            }
            
            .button-graphics::before, 
            .button-graphics::after {
                width: 40px;
                height: 40px;
            }
            
            .animated-text {
                font-size: 3.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .subtitle {
                font-size: 1rem;
                margin-bottom: 25px;
            }
            
            .business-btn {
                padding: 14px 24px;
                font-size: 15px;
            }
            
            .button-graphics::before, 
            .button-graphics::after {
                width: 30px;
                height: 30px;
            }
            
            .particles {
                display: none; /* Disable particles on very small screens for performance */
            }
            
            .animated-text {
                font-size: 2.5rem;
            }
            
            .hero-section {
                height: auto;
                min-height: 100vh;
            }
        }

        .container {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            max-height: 90vh;
        }

        .animated-text {
            font-size: 4.5rem;
            margin-bottom: 20px;
        }

        .button-container {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <!-- Particle animation background -->
        <div class="particles" id="particles"></div>
        
        <div class="container">
            <h1 class="animated-text">AI & GPS BASED ATTENDANCE SYSTEM</h1>
            <p class="subtitle">Streamline your attendance tracking with cutting-edge technology</p>
            <div class="button-container">
                <div class="button-graphics">
                    <a href="signup.php" class="business-btn pulse-animation">
                        <span>Get Started</span>
                        <i class="fas fa-arrow-right btn-icon"></i>
                        <div class="btn-highlight"></div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Create animated particles in the background
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random position
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                
                // Random size
                const size = Math.random() * 4 + 1;
                
                // Random animation duration and delay
                const duration = Math.random() * 10 + 8;
                const delay = Math.random() * 5;
                
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.opacity = Math.random() * 0.5 + 0.3;
                particle.style.animation = `float ${duration}s infinite ease-in-out ${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
            
            // Remove pulse animation after 5 seconds for less distraction
            setTimeout(() => {
                document.querySelector('.business-btn').classList.remove('pulse-animation');
            }, 5000);
        });
        
        // Add button click effect
        document.addEventListener('click', function(e) {
            if (e.target.closest('.business-btn')) {
                const btn = e.target.closest('.business-btn');
                
                // Create ripple effect
                const ripple = document.createElement('div');
                ripple.className = 'ripple';
                btn.appendChild(ripple);
                
                // Position ripple at click point
                const rect = btn.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height) * 2;
                ripple.style.width = `${size}px`;
                ripple.style.height = `${size}px`;
                ripple.style.left = `${e.clientX - rect.left - size/2}px`;
                ripple.style.top = `${e.clientY - rect.top - size/2}px`;
                
                // Remove after animation completes
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }
        });
    </script>
</body>
</html>
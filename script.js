const animatedText = document.querySelector('.animated-text');

document.addEventListener('mousemove', (event) => {
    const x = (event.clientX / window.innerWidth) * 100;

    animatedText.style.background = `linear-gradient(${x}deg, #ff6b6b, #f7b42c, #5fd3bc, #007bff)`;
    animatedText.style.backgroundSize = "300% 300%";
    animatedText.style.webkitBackgroundClip = "text";
    animatedText.style.webkitTextFillColor = "transparent";
});
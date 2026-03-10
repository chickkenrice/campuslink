/* login-bg.js - Particle Network Animation */

const canvas = document.getElementById('bg-canvas');
const ctx = canvas.getContext('2d');

// Configuration - Subtle settings
const numberOfParticles = 50;
const mouseRadius = 120; 
const baseSpeed = 0.3;

let particlesArray = [];

// Mouse position
let mouse = {
    x: null,
    y: null,
    radius: mouseRadius
}

window.addEventListener('mousemove', function(event) {
    mouse.x = event.x;
    mouse.y = event.y;
});

window.addEventListener('mouseout', function() {
    mouse.x = undefined;
    mouse.y = undefined;
});

// Particle Class
class Particle {
    constructor(x, y, directionX, directionY, size, color) {
        this.x = x;
        this.y = y;
        this.directionX = directionX;
        this.directionY = directionY;
        this.size = size;
        this.color = color;
    }

    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
        ctx.fillStyle = this.color;
        ctx.fill();
    }

    update() {
        if (this.x > canvas.width || this.x < 0) this.directionX = -this.directionX;
        if (this.y > canvas.height || this.y < 0) this.directionY = -this.directionY;

        // Mouse Interaction - Gentle push
        let dx = mouse.x - this.x;
        let dy = mouse.y - this.y;
        let distance = Math.sqrt(dx*dx + dy*dy);

        if (distance < mouse.radius + this.size) {
            if (mouse.x < this.x && this.x < canvas.width - this.size * 10) this.x += 0.5;
            if (mouse.x > this.x && this.x > this.size * 10) this.x -= 0.5;
            if (mouse.y < this.y && this.y < canvas.height - this.size * 10) this.y += 0.5;
            if (mouse.y > this.y && this.y > this.size * 10) this.y -= 0.5;
        }

        this.x += this.directionX;
        this.y += this.directionY;

        this.draw();
    }
}

function init() {
    particlesArray = [];
    for (let i = 0; i < numberOfParticles; i++) {
        let size = (Math.random() * 2.5) + 1;
        let x = (Math.random() * (canvas.width - size * 4)) + size * 2;
        let y = (Math.random() * (canvas.height - size * 4)) + size * 2;
        let directionX = (Math.random() * baseSpeed * 2) - baseSpeed;
        let directionY = (Math.random() * baseSpeed * 2) - baseSpeed;
        let color = '#8056ff';
        particlesArray.push(new Particle(x, y, directionX, directionY, size, color));
    }
}

function connect() {
    let maxDistance = (canvas.width/9) * (canvas.height/9);
    for (let a = 0; a < particlesArray.length; a++) {
        for (let b = a; b < particlesArray.length; b++) {
            let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x)) 
                         + ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
            
            if (distance < maxDistance) {
                let opacityValue = 0.6 - (distance/maxDistance) * 0.6;
                ctx.strokeStyle = 'rgba(128, 86, 255,' + opacityValue + ')';
                ctx.lineWidth = 0.8;
                ctx.beginPath();
                ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                ctx.stroke();
            }
        }
    }
}

function animate() {
    requestAnimationFrame(animate);
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let i = 0; i < particlesArray.length; i++) {
        particlesArray[i].update();
    }
    connect();
}

// Resize handler
function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    init();
}

window.addEventListener('resize', resize);

// Initialize and start
resize();
animate();
// Intersection Observer for scroll animations
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-fade-in');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll('section').forEach(section => {
    section.style.opacity = '0';
    observer.observe(section);
});

// Tab Switcher Logic
const tabData = {
    env: `SSLCOMMERZ_STORE_ID=your_id
SSLCOMMERZ_STORE_PASSWORD=secret
SSLCOMMERZ_SANDBOX=true`,
    config: `'store_id' => env('SSLCOMMERZ_STORE_ID'),
'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
'sandbox' => env('SSLCOMMERZ_SANDBOX', true),`
};

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Update Active Button
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Update Content
        const tab = btn.getAttribute('data-tab');
        document.getElementById('tab-code').textContent = tabData[tab];
    });
});

// Copy to Clipboard Functionality
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show a brief toast or change icon
        const icon = event.target;
        const originalClass = icon.className;
        icon.className = 'ph ph-check-circle';
        icon.style.color = '#22c55e';
        
        setTimeout(() => {
            icon.className = originalClass;
            icon.style.color = '';
        }, 2000);
    });
}

// Navbar Scroll Effect & Scroll Spy
window.addEventListener('scroll', () => {
    // Navbar effect
    const nav = document.getElementById('navbar');
    if (window.scrollY > 50) {
        nav.style.background = 'rgba(10, 10, 12, 0.9)';
        nav.style.padding = '10px 0';
    } else {
        nav.style.background = 'rgba(10, 10, 12, 0.1)';
        nav.style.padding = '20px 0';
    }

    // Scroll Spy for Documentation Sidebar
    const docSections = document.querySelectorAll('.doc-card');
    const docLinks = document.querySelectorAll('.docs-sidebar a');
    let current = '';

    docSections.forEach(section => {
        const rect = section.getBoundingClientRect();
        // If the top of the section is near the top of the viewport
        if (rect.top <= 150) {
            current = section.getAttribute('id');
        }
    });

    if (current) {
        docLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    }
});

// Smooth Scroll for Sidebar Links
document.querySelectorAll('.docs-sidebar a').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = link.getAttribute('href');
        const targetSection = document.querySelector(targetId);
        if (targetSection) {
            const targetTop = targetSection.getBoundingClientRect().top + window.pageYOffset;
            window.scrollTo({
                top: targetTop - 120,
                behavior: 'smooth'
            });
        }
    });
});

// Fetch Real Install Count from Packagist
async function fetchInstalls() {
    try {
        const response = await fetch('https://packagist.org/packages/sslcommerz/laravel.json');
        const data = await response.json();
        const totalInstalls = data.package.downloads.total;
        
        animateValue("install-count", 0, totalInstalls, 2000);
    } catch (error) {
        console.error("Failed to fetch installs", error);
        document.getElementById('install-count').textContent = '15,000+';
    }
}

function animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    const range = end - start;
    const minTimer = 50;
    let stepTime = Math.abs(Math.floor(duration / range));
    stepTime = Math.max(stepTime, minTimer);
    
    const startTime = new Date().getTime();
    const endTime = startTime + duration;
    let timer;

    function run() {
        const now = new Date().getTime();
        const remaining = Math.max((endTime - now) / duration, 0);
        const value = Math.round(end - (remaining * range));
        obj.innerHTML = value.toLocaleString() + '+';
        if (value == end) {
            clearInterval(timer);
        }
    }

    timer = setInterval(run, stepTime);
    run();
}

fetchInstalls();

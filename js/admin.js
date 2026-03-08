document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Navigation
    const navItems = document.querySelectorAll('.sidebar-nav li[data-target]');
    const contentSections = document.querySelectorAll('.content-section');
    const pageTitle = document.getElementById('page-title');

    const titles = {
        dashboard: 'Dashboard Overview',
        messages: 'Contact Messages',
        jobs: 'Job Applications',
        ambassadors: 'Ambassador Applications',
        partners: 'Partnership Requests',
        donations: 'Donations Tracking',
        visitors: 'Visitor Analytics',
        payments: 'Payment Settings',
        blog: 'Blog Management'
    };

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const target = item.getAttribute('data-target');

            // Update active nav
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');

            // Update visible section
            contentSections.forEach(section => section.classList.remove('active'));
            document.getElementById(`${target}-content`).classList.add('active');

            // Update title
            pageTitle.textContent = titles[target] || 'Admin';
        });
    });

    // Modal Logic
    const modal = document.getElementById('details-modal');
    const closeModal = document.querySelector('.close-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');

    closeModal.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) modal.style.display = 'none';
    };

    // Global view functions
    window.viewMessage = (data) => {
        modalTitle.textContent = 'Message Details';
        modalBody.innerHTML = `
            <div class="detail-item"><strong>From:</strong> ${data.name} (${data.email})</div>
            <div class="detail-item"><strong>Subject:</strong> ${data.subject}</div>
            <div class="detail-item"><strong>Date:</strong> ${new Date(data.created_at).toLocaleString()}</div>
            <div class="detail-item"><strong>Message:</strong><br><p>${data.message}</p></div>
        `;
        modal.style.display = 'block';
    };

    window.viewJob = (data) => {
        modalTitle.textContent = 'Job Application Details';
        modalBody.innerHTML = `
            <div class="detail-item"><strong>Applicant:</strong> ${data.name}</div>
            <div class="detail-item"><strong>Email:</strong> ${data.email}</div>
            <div class="detail-item"><strong>Position:</strong> ${data.position}</div>
            <div class="detail-item"><strong>Date:</strong> ${new Date(data.created_at).toLocaleString()}</div>
            <div class="detail-item"><strong>Resume Link:</strong> <a href="${data.resume_link}" target="_blank">${data.resume_link}</a></div>
        `;
        modal.style.display = 'block';
    };

    window.viewAmbassador = (data) => {
        modalTitle.textContent = 'Ambassador Details';
        modalBody.innerHTML = `
            <div class="detail-item"><strong>Name:</strong> ${data.name}</div>
            <div class="detail-item"><strong>Email:</strong> ${data.email}</div>
            <div class="detail-item"><strong>University:</strong> ${data.university}</div>
            <div class="detail-item"><strong>Course:</strong> ${data.course}</div>
            <div class="detail-item"><strong>Year:</strong> ${data.year_of_study}</div>
            <div class="detail-item"><strong>Date:</strong> ${new Date(data.created_at).toLocaleString()}</div>
        `;
        modal.style.display = 'block';
    };

    window.viewPartner = (data) => {
        modalTitle.textContent = 'Partner Details';
        modalBody.innerHTML = `
            <div class="detail-item"><strong>Name:</strong> ${data.name}</div>
            <div class="detail-item"><strong>Email:</strong> ${data.email}</div>
            <div class="detail-item"><strong>Organization:</strong> ${data.organization || '-'}</div>
            <div class="detail-item"><strong>Type:</strong> ${data.partnership_type}</div>
            <div class="detail-item"><strong>Focus Area:</strong> ${data.focus_area}</div>
            <div class="detail-item"><strong>Date:</strong> ${new Date(data.created_at).toLocaleString()}</div>
            <div class="detail-item"><strong>Message:</strong><br><p>${data.message}</p></div>
        `;
        modal.style.display = 'block';
    };

    window.editPost = (post) => {
        const idField = document.getElementById('blog_id');
        const titleField = document.getElementById('blog_title');
        const contentField = document.getElementById('blog_content');
        const videoField = document.getElementById('blog_video_url');
        const audioField = document.getElementById('blog_audio_url');

        if (!idField || !titleField || !contentField) {
            return;
        }

        idField.value = post.id || '';
        titleField.value = post.title || '';
        contentField.value = post.content || '';

        if (videoField) {
            videoField.value = post.video_url || '';
        }
        if (audioField) {
            audioField.value = post.audio_url || '';
        }

        const blogSection = document.getElementById('blog-content');
        if (blogSection) {
            contentSections.forEach(section => section.classList.remove('active'));
            blogSection.classList.add('active');
        }

        navItems.forEach(nav => nav.classList.remove('active'));
        const blogNav = document.querySelector('.sidebar-nav li[data-target="blog"]');
        if (blogNav) {
            blogNav.classList.add('active');
        }

        pageTitle.textContent = titles.blog;

        blogSection?.scrollIntoView({ behavior: 'smooth' });
    };

    const adminThemeToggle = document.getElementById('admin-theme-toggle');
    if (adminThemeToggle) {
        const body = document.body;
        const icon = adminThemeToggle.querySelector('i');
        const savedTheme = localStorage.getItem('adminTheme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-mode');
            if (icon) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }
        adminThemeToggle.addEventListener('click', () => {
            if (body.classList.contains('dark-mode')) {
                body.classList.remove('dark-mode');
                localStorage.setItem('adminTheme', 'light');
                if (icon) {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            } else {
                body.classList.add('dark-mode');
                localStorage.setItem('adminTheme', 'dark');
                if (icon) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            }
        });
    }
});

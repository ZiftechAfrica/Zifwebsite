document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Navigation
    const navItems = document.querySelectorAll('.sidebar-nav li[data-target]');
    const contentSections = document.querySelectorAll('.content-section');
    const pageTitle = document.getElementById('page-title');

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
            const titles = {
                dashboard: 'Dashboard Overview',
                messages: 'Contact Messages',
                jobs: 'Job Applications',
                ambassadors: 'Ambassador Applications',
                donations: 'Donations Tracking'
            };
            pageTitle.textContent = titles[target];
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
});

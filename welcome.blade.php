<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Intern Performance Monitoring System</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
            <style>
            .hero-section {
                background-color: #f8f9fa;
                padding: 100px 0 60px;
                margin-bottom: 0;
            }
            .feature-card {
                border: 1px solid #dee2e6;
                border-radius: 12px;
                padding: 40px 25px;
                margin-bottom: 30px;
                transition: all 0.3s ease;
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                background: #fff;
            }
            .feature-card:hover {
                transform: translateY(-5px);
                border-color: #0d6efd;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            }
            .feature-icon {
                font-size: 3rem;
                margin-bottom: 25px;
                color: #0d6efd;
                background: rgba(13, 110, 253, 0.1);
                width: 80px;
                height: 80px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.3s ease;
            }
            .feature-card:hover .feature-icon {
                background: #0d6efd;
                color: #fff;
            }
            .feature-card h5 {
                font-size: 1.25rem;
                font-weight: 600;
                margin-bottom: 15px;
                color: #212529;
            }
            .feature-card p {
                font-size: 0.95rem;
                line-height: 1.6;
                color: #6c757d;
                margin: 0;
            }
            .navbar {
                padding: 15px 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }
            .navbar-brand {
                display: flex;
                align-items: center;
                font-size: 1.25rem;
            }
            .navbar-brand i {
                transition: transform 0.3s ease;
            }
            .navbar-brand:hover i {
                transform: scale(1.1);
            }
            .nav-link {
                font-weight: 500;
                color: #495057;
                transition: color 0.3s ease;
            }
            .nav-link:hover {
                color: #0d6efd;
            }
            .navbar .btn-primary {
                padding: 8px 20px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            .navbar .btn-primary:hover {
                background: #0b5ed7;
                color: #fff;
            }
            .hero-content {
                padding-right: 40px;
            }
            .hero-image {
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                height: 400px;
                background-image: url('{{ asset('images/hero-background.jpeg') }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-color: #fff;
                width: 100%;
                object-fit: cover;
            }
            .section-title {
                margin-bottom: 40px;
                position: relative;
                padding-bottom: 15px;
            }
            .section-title:after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 50px;
                height: 2px;
                background-color: #0d6efd;
            }
            .features-section {
                padding: 60px 0;
                background-color: #f8f9fa;
            }
            .btn-lg {
                padding: 12px 30px;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 40px 0;
                margin-top: 0;
            }
            .footer-links {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .footer-links li {
                margin-bottom: 10px;
            }
            .footer-links a {
                color: #6c757d;
                text-decoration: none;
                transition: color 0.3s;
            }
            .footer-links a:hover {
                color: #0d6efd;
            }
            .social-links {
                display: flex;
                gap: 15px;
                margin-top: 20px;
            }
            .social-links a {
                color: #6c757d;
                font-size: 1.5rem;
                transition: color 0.3s;
            }
            .social-links a:hover {
                color: #0d6efd;
            }
            @media (max-width: 991.98px) {
                .hero-content {
                    padding-right: 0;
                    margin-bottom: 40px;
                    text-align: center;
                }
                .hero-section {
                    padding: 80px 0;
                }
                .d-flex.gap-3 {
                    justify-content: center;
                }
                .navbar-collapse {
                    background: #fff;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    margin-top: 15px;
                }
                .navbar-nav {
                    gap: 10px;
                }
                .nav-item {
                    margin: 0 !important;
                }
            }
            .process-card {
                padding: 30px 20px;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                position: relative;
                transition: transform 0.3s ease;
            }
            .process-card:hover {
                transform: translateY(-5px);
            }
            .process-icon {
                font-size: 2.5rem;
                color: #0d6efd;
                margin-bottom: 20px;
            }
            .process-number {
                position: absolute;
                top: -15px;
                right: -15px;
                width: 30px;
                height: 30px;
                background: #0d6efd;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }
            .testimonial-card {
                padding: 40px;
                background: #fff;
                border-radius: 15px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .testimonial-image {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
            }
            .testimonial-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .testimonial-text {
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 20px;
                color: #6c757d;
            }
            .testimonial-name {
                font-weight: 600;
                margin-bottom: 5px;
            }
            .testimonial-role {
                color: #6c757d;
                margin-bottom: 0;
            }
            .partner-logo {
                padding: 20px;
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }
            .partner-logo:hover {
                transform: scale(1.05);
            }
            .partner-logo img {
                max-height: 60px;
                width: auto;
                margin: 0 auto;
                display: block;
            }
            .accordion-button:not(.collapsed) {
                background-color: #e7f1ff;
                color: #0d6efd;
            }
            .accordion-button:focus {
                box-shadow: none;
                border-color: rgba(0,0,0,.125);
            }
            .cta-section {
                position: relative;
                overflow: hidden;
            }
            .cta-section::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(45deg, rgba(13,110,253,0.9), rgba(13,202,240,0.9));
                z-index: 1;
            }
            .cta-section .container {
                position: relative;
                z-index: 2;
            }
            .timeline-section {
                padding: 60px 0;
                background-color: #f8f9fa;
            }
            .timeline-container {
                position: relative;
                padding: 20px 0;
            }
            .vertical-scrollable-timeline {
                position: relative;
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .vertical-scrollable-timeline li {
                position: relative;
                padding: 30px;
                background: #fff;
                border-radius: 10px;
                margin-bottom: 30px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                transition: transform 0.3s ease;
            }
            .vertical-scrollable-timeline li:hover {
                transform: translateY(-5px);
            }
            .vertical-scrollable-timeline li:last-child {
                margin-bottom: 0;
            }
            .list-progress {
                position: absolute;
                left: 50%;
                top: 0;
                bottom: 0;
                width: 2px;
                background: #e9ecef;
                transform: translateX(-50%);
            }
            .list-progress .inner {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 0;
                background: #0d6efd;
                transition: height 0.3s ease;
            }
            .icon-holder {
                position: absolute;
                right: -20px;
                top: 50%;
                transform: translateY(-50%);
                width: 40px;
                height: 40px;
                background: #0d6efd;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 1.2rem;
            }
            .vertical-scrollable-timeline h4 {
                color: #212529;
                font-weight: 600;
            }
            .vertical-scrollable-timeline p {
                color: #6c757d;
                margin-bottom: 0;
            }
            .custom-btn {
                background: transparent;
                border: 2px solid #fff;
                color: #fff;
                padding: 10px 25px;
                border-radius: 25px;
                transition: all 0.3s ease;
            }
            .custom-btn:hover {
                background: #fff;
                color: #13547a;
            }
            .custom-border-btn {
                background: #fff;
                color: #13547a;
            }
            .custom-border-btn:hover {
                background: transparent;
                color: #fff;
            }
            .statistics-section {
                padding: 80px 0;
                background-color: #fff;
            }
            .stat-item {
                padding: 20px;
                transition: transform 0.3s ease;
            }
            .stat-item:hover {
                transform: translateY(-5px);
            }
            .stat-icon {
                font-size: 2.5rem;
                color: #0d6efd;
                margin-bottom: 15px;
            }
            .stat-number {
                font-size: 2.5rem;
                font-weight: 700;
                color: #212529;
                margin-bottom: 10px;
            }
            .stat-text {
                color: #6c757d;
                margin: 0;
            }
            .benefits-section {
                padding: 60px 0;
            }
            .benefit-card {
                background: #fff;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                height: 100%;
                transition: transform 0.3s ease;
            }
            .benefit-card:hover {
                transform: translateY(-5px);
            }
            .benefit-icon {
                font-size: 2rem;
                color: #0d6efd;
                margin-bottom: 20px;
            }
            .benefit-card h4 {
                font-size: 1.25rem;
                font-weight: 600;
                margin-bottom: 15px;
                color: #212529;
            }
            .benefit-card p {
                color: #6c757d;
                margin: 0;
            }
            .faq-section {
                padding: 60px 0;
                background-color: #fff;
            }
            .accordion-item {
                border: 1px solid rgba(0,0,0,.125);
                margin-bottom: 15px;
                border-radius: 8px !important;
                overflow: hidden;
            }
            .accordion-button {
                padding: 20px;
                font-weight: 500;
                color: #212529;
            }
            .accordion-button:not(.collapsed) {
                background-color: #f8f9fa;
                color: #0d6efd;
            }
            .accordion-button:focus {
                box-shadow: none;
                border-color: rgba(0,0,0,.125);
            }
            .accordion-body {
                padding: 20px;
                color: #6c757d;
            }
            .accordion-button::after {
                background-size: 16px;
            }
            .contact-section {
                padding: 0;
                background-color: #f8f9fa;
            }
            .contact-form-wrap {
                background: #fff;
                padding: 60px 0;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            .contact-form .input-group {
                max-width: 500px;
                margin: 30px auto 0;
            }
            .contact-form .form-control {
                height: 55px;
                border-radius: 30px 0 0 30px;
                border: 2px solid #e9ecef;
                padding: 0 25px;
                font-size: 1rem;
            }
            .contact-form .form-control:focus {
                box-shadow: none;
                border-color: #0d6efd;
            }
            .contact-form .btn {
                height: 55px;
                border-radius: 0 30px 30px 0;
                padding: 0 35px;
                font-weight: 500;
                font-size: 1rem;
            }
            .contact-form-wrap p {
                color: #6c757d;
                line-height: 1.8;
                font-size: 1.1rem;
                max-width: 700px;
                margin: 0 auto;
            }
            .contact-form-wrap .section-title {
                margin-bottom: 25px;
            }
            </style>
    </head>
    
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="bi bi-briefcase" style="font-size: 1.5rem; color: #0d6efd;"></i>
                    <span class="fw-bold ms-2">IPMS</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item me-4">
                            <a class="nav-link" href="#home">Dashboard</a>
                        </li>
                        <li class="nav-item me-4">
                            <a class="nav-link" href="#features">Features</a>
                        </li>
                        <li class="nav-item me-4">
                            <a class="nav-link" href="#section_3">How It Works</a>
                        </li>
                        <li class="nav-item me-4">
                            <a class="nav-link" href="#benefits">Why Choose Us</a>
                        </li>
                        <li class="nav-item me-4">
                            <a class="nav-link" href="#faq">FAQ</a>
                        </li>
                        <li class="nav-item me-4">
                            <a class="nav-link" href="#contact">Contact</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="btn btn-primary px-4">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Apply
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section" id="home">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 hero-content">
                        <h1 class="display-4 fw-bold mb-4">Smart Intern Management</h1>
                        <p class="lead mb-5">Track performance, provide feedback, and faster growth - all in single platform.</p>
                        <div class="d-flex gap-3">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Get Started</a>
                            <a href="#features" class="btn btn-outline-primary btn-lg">Learn More</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-image">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <div class="container">
                <h2 class="text-center section-title">Key Features</h2>
                <div class="row g-4">
                    <!-- Admin Dashboard -->
                    <div class="col-md-6 col-lg-3">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-speedometer2"></i>
                            </div>
                            <h5>Admin Dashboard</h5>
                            <p>Comprehensive overview of intern performance metrics and analytics.</p>
                        </div>
                    </div>
                    <!-- Supervisor Tools -->
                    <div class="col-md-6 col-lg-3">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-person-workspace"></i>
                            </div>
                            <h5>Supervisor Tools</h5>
                            <p>Powerful tools for managing and evaluating intern performance.</p>
                        </div>
                    </div>
                    <!-- Intern Portal -->
                    <div class="col-md-6 col-lg-3">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                            <h5>Intern Portal</h5>
                            <p>Dedicated space for interns to track progress and receive feedback.</p>
                        </div>
                    </div>
                    <!-- Analytics -->
                    <div class="col-md-6 col-lg-3">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h5>Analytics</h5>
                            <p>Data-driven insights for better decision making and performance tracking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="timeline-section section-padding" id="section_3">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <h2 class="section-title">How does it work?</h2>
                    </div>

                    <div class="col-lg-10 col-12 mx-auto">
                        <div class="timeline-container">
                            <ul class="vertical-scrollable-timeline" id="vertical-scrollable-timeline">
                                <div class="list-progress">
                                    <div class="inner"></div>
                                </div>

                                <li>
                                    <h4 class="mb-3">Register & Set Up</h4>
                                    <p>Create your account and set up your profile. Choose your role (Admin, Supervisor, or Intern) and get started with your personalized dashboard.</p>
                                    <div class="icon-holder">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                </li>
                                
                                <li>
                                    <h4 class="mb-3">Schedule & Plan</h4>
                                    <p>Set up your internship timeline, define goals, and create a structured plan for success. Our platform helps you organize tasks and milestones effectively.</p>
                                    <div class="icon-holder">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                </li>

                                <li>
                                    <h4 class="mb-3">Track & Monitor</h4>
                                    <p>Monitor progress in real-time, receive instant feedback, and track performance metrics. Stay informed about every aspect of the internship journey.</p>
                                    <div class="icon-holder">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                </li>

                                <li>
                                    <h4 class="mb-3">Excel & Grow</h4>
                                    <p>Complete your internship successfully with comprehensive support. Our platform ensures continuous growth and development throughout your journey.</p>
                                    <div class="icon-holder">
                                        <i class="bi bi-trophy"></i>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 text-center mt-5">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Get Started</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="benefits-section section-padding bg-light" id="benefits">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center mb-5">
                        <h2 class="section-title">Why Choose Us</h2>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <h4>Time Efficient</h4>
                            <p>Streamline your intern management process and save valuable time with automated workflows and smart scheduling.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h4>Secure & Reliable</h4>
                            <p>Your data is protected with enterprise-grade security measures and regular backups.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <h4>Performance Tracking</h4>
                            <p>Monitor intern progress with detailed analytics and real-time performance metrics.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <h4>Better Communication</h4>
                            <p>Facilitate seamless communication between supervisors and interns with built-in messaging.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <h4>Easy Documentation</h4>
                            <p>Keep all intern records and evaluations organized in one centralized location.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <h4>Continuous Support</h4>
                            <p>Get 24/7 support and regular updates to ensure smooth operation of your intern program.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section section-padding" id="faq">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center mb-5">
                        <h2 class="section-title">Frequently Asked Questions</h2>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        What is IPMS?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        IPMS (Intern Performance Monitoring System) is a comprehensive platform designed to streamline and enhance the internship management process. It helps organizations track, evaluate, and improve intern performance effectively.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        How does the evaluation process work?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Our evaluation process is comprehensive and transparent. Supervisors can set specific goals, track progress, provide real-time feedback, and conduct regular assessments. The system automatically generates performance reports and analytics.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Can I customize the evaluation criteria?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes, the platform allows you to customize evaluation criteria based on your organization's specific needs. You can set different parameters, weightage, and assessment methods for different roles and departments.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        Is there a mobile app available?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes, IPMS is fully responsive and works seamlessly on all devices. You can access all features through any web browser on your mobile device, tablet, or desktop computer.
                                    </div>
                                </div>
        </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                        How secure is the platform?
                                    </button>
                                </h2>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Security is our top priority. We implement industry-standard encryption, regular security audits, and strict access controls to ensure your data remains protected at all times.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Get in Touch Section -->
        <section class="contact-section section-padding" id="contact">
            <div class="container-fluid px-0">
                <div class="row g-0">
                    <div class="col-12">
                        <div class="contact-form-wrap text-center">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8 col-md-10 col-12">
                                        <h2 class="section-title mb-4">Get in Touch</h2>
                                        
                                        <p class="mb-4">
                                            Although the system is custom-built and still under development, your feedback is always welcome! While we're not handling general inquiries at this time, feel free to submit a ticket if you encounter any issues or have suggestions.
                                            <br><br>
                                            Your input helps us improve—thank you for your support!
                                        </p>

                                        <form class="contact-form" action="#" method="post" role="form">
                                            <div class="input-group">
                                                <input type="email" name="contact-email" id="contact-email" class="form-control" 
                                                    placeholder="Email Address" required>
                                                <button type="submit" class="btn btn-primary">
                                                    Submit
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <h5 class="mb-3">IPMS</h5>
                        <p class="text-muted">Empowering intern management through technology and innovation.</p>
                        <div class="social-links">
                            <a href="#"><i class="bi bi-facebook"></i></a>
                            <a href="#"><i class="bi bi-twitter"></i></a>
                            <a href="#"><i class="bi bi-linkedin"></i></a>
                            <a href="#"><i class="bi bi-instagram"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <h5 class="mb-3">Quick Links</h5>
                        <ul class="footer-links">
                            <li><a href="#home">Dashboard</a></li>
                            <li><a href="#features">Features</a></li>
                            <li><a href="/login">Login</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-4">
                        <h5 class="mb-3">Contact Us</h5>
                        <ul class="footer-links">
                            <li><i class="bi bi-geo-alt me-2"></i> TCU Campus, City</li>
                            <li><i class="bi bi-telephone me-2"></i> (123) 456-7890</li>
                            <li><i class="bi bi-envelope me-2"></i> support@ipms.com</li>
                        </ul>
                    </div>
                </div>
                <hr class="my-4">
                <div class="text-center text-muted">
                    <small>&copy; {{ date('Y') }} Intern Performance Monitoring System. All rights reserved.</small>
                </div>
            </div>
        </footer>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
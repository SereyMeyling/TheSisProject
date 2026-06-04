@extends('layouts.app')

@section('content')
    <!DOCTYPE html>

    <html class="light" lang="en">

    <head>
        <meta charset="utf-8" />
        <meta content="width=device-width, initial-scale=1.0" name="viewport" />
        <title>VitaCare Clinic | Welcome to Quality Care</title>
        <!-- Google Fonts -->
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Manrope:wght@600;700;800&amp;display=swap"
            rel="stylesheet" />
        <!-- Material Symbols -->
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
            rel="stylesheet" />
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
            rel="stylesheet" />
        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <script id="tailwind-config">
            tailwind.config = {
                darkMode: "class",
                theme: {
                    extend: {
                        "colors": {
                            "on-surface-variant": "#3d4a3e",
                            "on-secondary": "#ffffff",
                            "surface-bright": "#f7f9fb",
                            "secondary": "#b70070",
                            "tertiary-fixed": "#d5e3ff",
                            "secondary-container": "#fe4da7",
                            "on-error": "#ffffff",
                            "on-primary": "#ffffff",
                            "tertiary-fixed-dim": "#b0c7f1",
                            "secondary-fixed-dim": "#ffb0ce",
                            "primary-fixed-dim": "#51e084",
                            "inverse-surface": "#2d3133",
                            "surface-variant": "#e0e3e5",
                            "on-secondary-container": "#5c0035",
                            "primary": "#006d36",
                            "on-tertiary-container": "#1b3355",
                            "on-primary-fixed": "#00210c",
                            "primary-container": "#00b25c",
                            "primary-fixed": "#70fd9d",
                            "surface-container-lowest": "#ffffff",
                            "surface-container-low": "#f2f4f6",
                            "on-background": "#191c1e",
                            "surface-container-high": "#e6e8ea",
                            "on-primary-fixed-variant": "#005227",
                            "on-error-container": "#93000a",
                            "on-secondary-fixed": "#3e0022",
                            "secondary-fixed": "#ffd9e5",
                            "on-tertiary-fixed": "#001b3c",
                            "inverse-on-surface": "#eff1f3",
                            "surface-dim": "#d8dadc",
                            "error-container": "#ffdad6",
                            "tertiary-container": "#859cc4",
                            "tertiary": "#485f84",
                            "background": "#f7f9fb",
                            "outline": "#6d7b6d",
                            "surface-container": "#eceef0",
                            "on-surface": "#191c1e",
                            "surface-container-highest": "#e0e3e5",
                            "error": "#ba1a1a",
                            "outline-variant": "#bccabb",
                            "on-tertiary": "#ffffff",
                            "on-secondary-fixed-variant": "#8c0054",
                            "surface": "#f7f9fb",
                            "on-primary-container": "#003b1a",
                            "surface-tint": "#006d36",
                            "inverse-primary": "#51e084",
                            "on-tertiary-fixed-variant": "#30476a"
                        },
                        "borderRadius": {
                            "DEFAULT": "0.25rem",
                            "lg": "0.5rem",
                            "xl": "0.75rem",
                            "full": "9999px"
                        },
                        "spacing": {
                            "xl": "80px",
                            "sm": "12px",
                            "gutter": "24px",
                            "md": "24px",
                            "margin-desktop": "64px",
                            "base": "8px",
                            "xs": "4px",
                            "margin-mobile": "16px",
                            "lg": "48px"
                        },
                        "fontFamily": {
                            "headline-lg": ["Manrope"],
                            "body-lg": ["Inter"],
                            "body-md": ["Inter"],
                            "label-sm": ["Inter"],
                            "display-lg": ["Manrope"],
                            "headline-md": ["Manrope"],
                            "label-md": ["Inter"],
                            "headline-lg-mobile": ["Manrope"]
                        },
                        "fontSize": {
                            "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "600" }],
                            "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                            "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                            "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                            "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                            "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
                            "label-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.01em", "fontWeight": "500" }],
                            "headline-lg-mobile": ["28px", { "lineHeight": "36px", "fontWeight": "600" }]
                        }
                    },
                },
            }
        </script>
        <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                display: inline-block;
                vertical-align: middle;
            }

            .glass-card {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.3);
            }

            .tap-highlight-transparent {
                -webkit-tap-highlight-color: transparent;
            }
        </style>
    </head>

    <body class="bg-background text-on-surface selection:bg-primary-container selection:text-on-primary-container">

        <main class="pt-20">
            <!-- Hero Section -->
            <section class="relative h-[85vh] min-h-[600px] flex items-center overflow-hidden">
                <div class="absolute inset-0 z-0">
                    <img alt="VitaCare Clinic Modern Lobby" class="w-full h-full object-cover"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDuAa9iPdyIozhGLY9ZUwbxfqf9nwS_2yQHJ62_oQL-XHJD-nklfSd-c34jl3FYo-JSDewRAbnIK7TdeWtp9s5BMGxl-HdWfBfw642qqcIkM1RXNpcFFdkJxG3OPv7Dp5evSbjvbozAErPUjsQ-fpeXXWe3Y1izfRHfgDXU1XswiDT1HLSX1KLL5tUhqfC9FSgf0nQAflE88KSuoBj-T2Snm3Qe7xJkkCE2eHB7o1YuDyaLxaSs03kccI0kl5DK1Lph_JTQ0uMjd7E" />
                    <div class="absolute inset-0 bg-gradient-to-r from-background/90 via-background/40 to-transparent">
                    </div>
                </div>
                <div class="relative z-10 max-w-[1280px] mx-auto px-margin-desktop w-full">
                    <div class="max-w-2xl">
                        <span
                            class="inline-block px-4 py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm mb-6 animate-fade-in">
                            TRUSTED HEALTHCARE SOLUTIONS
                        </span>
                        <h1
                            class="font-display-lg text-display-lg md:text-[64px] md:leading-[72px] text-on-background mb-6">
                            ស្វាគមន៏មកកាន់ <span class="text-primary">PRUM SANTEPHEAP CLINIC</span>
                        </h1>
                        <p class="font-body-lg text-body-lg text-on-surface-variant mb-10 max-w-lg">
                            Experience a new standard of healthcare where precision meets empathy. Our world-class
                            specialists are dedicated to your long-term wellbeing in a tranquil, modern environment.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <button
                                class="bg-primary text-on-primary px-8 py-4 rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:translate-y-[-2px] transition-all active:scale-95 flex items-center gap-2">
                                <span class="material-symbols-outlined">login</span>
                                Patient Login
                            </button>
                            <button
                                class="bg-surface-container-lowest text-primary border border-outline-variant px-8 py-4 rounded-xl font-label-md text-label-md hover:bg-surface-container-low transition-all active:scale-95 flex items-center gap-2">
                                <span class="material-symbols-outlined">badge</span>
                                Staff Portal
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Highlights Bento Grid -->
            <section class="py-xl bg-surface-bright">
                <div class="max-w-[1280px] mx-auto px-margin-desktop">
                    <div class="text-center mb-16">
                        <h2 class="font-headline-lg text-headline-lg text-on-background mb-4">Redefining the Patient
                            Experience</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant max-w-xl mx-auto">Providing
                            comprehensive medical care through innovation, expertise, and a patient-first philosophy.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                        <!-- Highlight 1 -->
                        <div
                            class="md:col-span-8 group relative overflow-hidden rounded-3xl bg-white shadow-[0px_4px_20px_rgba(29,53,87,0.05)] p-10 transition-all hover:shadow-xl">
                            <div class="flex flex-col md:flex-row gap-8 items-center">
                                <div class="flex-1">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-primary-container/20 text-primary flex items-center justify-center mb-6">
                                        <span class="material-symbols-outlined text-3xl">groups</span>
                                    </div>
                                    <h3 class="font-headline-md text-headline-md text-on-background mb-4">Expert Doctors
                                    </h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant">Our team consists of
                                        board-certified specialists with decades of collective experience in complex medical
                                        cases and preventative health.</p>
                                </div>
                                <div class="w-full md:w-1/2 aspect-video rounded-2xl overflow-hidden">
                                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        data-alt="A diverse team of professional medical doctors in white lab coats and stethoscopes standing confidently in a modern, brightly lit hospital corridor. The lighting is soft and clinical, conveying expertise and trust. The overall atmosphere is professional, clean, and optimistic, matching the clinic's premium medical branding."
                                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDBTU-GT3IG3PkSqBZLCE6j541fOjlNB85TAGoLg3N4Oyq5u6SJ72UwDaU7tEdJY-s0bY2xH8Yluk0u3Tb8teAgbaeGBzUmnX4OUvRlo8c1psrnJWbyWcaQS9E27LB3Rl2I-wzP9eKXZ0yZxG86_5n3F1JqI5aojx3k-SY5awjGySF8oalSAC1-LUT7MPl27iJGufYL5d4n6wgdkONt8XVITh1PTWzRAonShNGt3nNI3LfFTb2CExWOgxTrw-eRUV2rXginaEDJBes" />
                                </div>
                            </div>
                        </div>
                        <!-- Highlight 2 -->
                        <div
                            class="md:col-span-4 bg-primary text-on-primary rounded-3xl p-10 flex flex-col justify-between shadow-lg shadow-primary/10">
                            <div>
                                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center mb-6">
                                    <span class="material-symbols-outlined text-3xl text-white">apartment</span>
                                </div>
                                <h3 class="font-headline-md text-headline-md mb-4">Modern Facilities</h3>
                                <p class="font-body-md text-body-md opacity-90">State-of-the-art diagnostic tools and
                                    comfortable recovery suites designed for tranquility and faster healing.</p>
                            </div>
                            <div class="mt-8">
                                <a class="inline-flex items-center gap-2 font-label-md text-label-md hover:underline"
                                    href="#">
                                    Take a Virtual Tour <span class="material-symbols-outlined">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                        <!-- Highlight 3 -->
                        <div
                            class="md:col-span-4 bg-secondary-container text-on-secondary-container rounded-3xl p-10 flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center mb-6">
                                    <span class="material-symbols-outlined text-3xl">favorite</span>
                                </div>
                                <h3 class="font-headline-md text-headline-md mb-4">Compassionate Care</h3>
                                <p class="font-body-md text-body-md opacity-90">We treat the person, not just the symptom.
                                    Every patient journey is personalized to your unique needs.</p>
                            </div>
                            <div class="mt-8">
                                <span class="text-label-sm font-label-sm uppercase tracking-wider">Patient Choice 2024
                                    Winner</span>
                            </div>
                        </div>
                        <!-- Highlight 4 -->
                        <div
                            class="md:col-span-8 bg-white rounded-3xl p-10 shadow-[0px_4px_20px_rgba(29,53,87,0.05)] border border-outline-variant/30 flex items-center">
                            <div class="flex-1">
                                <h3 class="font-headline-md text-headline-md text-on-background mb-4">Direct Communication
                                </h3>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-6">Access your medical
                                    records, message your doctor, and request prescriptions directly through our secure
                                    patient portal.</p>
                                <div class="flex gap-4">
                                    <div
                                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container-low text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary">verified_user</span>
                                        <span class="text-label-sm">HIPAA Compliant</span>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container-low text-on-surface-variant">
                                        <span class="material-symbols-outlined text-primary">speed</span>
                                        <span class="text-label-sm">Instant Records</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Appointment CTA Bar -->
            <div class="bg-secondary text-on-secondary py-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-1/3 h-full bg-white/10 skew-x-[45deg] translate-x-20"></div>
                <div
                    class="max-w-[1280px] mx-auto px-margin-desktop flex flex-col md:flex-row justify-between items-center gap-6 relative z-10">
                    <div class="flex items-center gap-4">
                        <span class="material-symbols-outlined text-4xl">event_available</span>
                        <div>
                            <h4 class="font-headline-md text-headline-md">Ready to prioritize your health?</h4>
                            <p class="font-body-md text-body-md opacity-90">Book your first consultation today with our
                                specialists.</p>
                        </div>
                    </div>
                    <button
                        class="bg-white text-secondary px-10 py-4 rounded-xl font-label-md text-label-md font-bold shadow-xl hover:bg-secondary-fixed transition-all active:scale-95 whitespace-nowrap">
                        Book Now
                    </button>
                </div>
            </div>
        </main>
        <!-- Footer -->
        <footer class="w-full bg-surface-container-lowest border-t border-outline-variant">
            <div class="max-w-[1280px] mx-auto px-margin-desktop py-12">
                
                <div
                    class="mt-12 pt-8 border-t border-outline-variant/30 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-on-surface-variant font-body-md text-body-md">© 2026 PRUM SANTEPHEAP CLINIC. All rights
                        reserved.</p>
                    <div class="flex gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-on-surface-variant hover:bg-primary-container hover:text-on-primary-container transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-lg">share</span>
                        </div>
                        <div
                            class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-on-surface-variant hover:bg-primary-container hover:text-on-primary-container transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-lg">public</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Mobile Bottom NavBar -->
        <nav
            class="md:hidden fixed bottom-0 w-full z-50 bg-surface border-t border-outline-variant shadow-[0_-4px_20px_rgba(29,53,87,0.05)] flex justify-around items-center px-4 py-3">
            <div class="flex flex-col items-center justify-center text-primary font-semibold tap-highlight-transparent">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">home</span>
                <span class="font-label-sm text-[10px] mt-1">Home</span>
            </div>
            <div
                class="flex flex-col items-center justify-center text-on-surface-variant tap-highlight-transparent active:bg-surface-container-high rounded-lg px-2">
                <span class="material-symbols-outlined">calendar_today</span>
                <span class="font-label-sm text-[10px] mt-1">Appointments</span>
            </div>
            <div
                class="flex flex-col items-center justify-center text-on-surface-variant tap-highlight-transparent active:bg-surface-container-high rounded-lg px-2">
                <span class="material-symbols-outlined">chat_bubble</span>
                <span class="font-label-sm text-[10px] mt-1">Messages</span>
            </div>
            <div
                class="flex flex-col items-center justify-center text-on-surface-variant tap-highlight-transparent active:bg-surface-container-high rounded-lg px-2">
                <span class="material-symbols-outlined">person</span>
                <span class="font-label-sm text-[10px] mt-1">Profile</span>
            </div>
        </nav>
        <script>
            // Simple scroll effect for TopAppBar
            window.addEventListener('scroll', () => {
                const header = document.querySelector('header');
                if (window.scrollY > 10) {
                    header.classList.add('shadow-md');
                    header.classList.remove('shadow-sm');
                } else {
                    header.classList.add('shadow-sm');
                    header.classList.remove('shadow-md');
                }
            });
        </script>
    </body>

    </html>
@endsection

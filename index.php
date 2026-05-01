<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexForm – Advanced Ajax PHP Form Builder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/nexform.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary:   #6366f1;
            --primary-d: #4f46e5;
            --dark:      #0f172a;
            --text:      #1e293b;
            --muted:     #64748b;
            --bg:        #f8fafc;
            --card:      #ffffff;
            --border:    #e2e8f0;
            --radius:    14px;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }

        /* ---- HERO ---- */
        .hero {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
            padding: 100px 20px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            color: #fff;
            padding: 5px 16px;
            border-radius: 99px;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 22px;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.6rem);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 18px;
            letter-spacing: -.02em;
        }
        .hero h1 span { background: linear-gradient(90deg, #fbbf24, #f87171); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p {
            font-size: 1.15rem;
            color: rgba(255,255,255,.8);
            max-width: 620px;
            margin: 0 auto 36px;
        }
        .hero-cta { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .btn-white {
            background: #fff;
            color: var(--primary-d);
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: .95rem;
            text-decoration: none;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
        }
        .btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .btn-outline-white {
            border: 2px solid rgba(255,255,255,.5);
            color: #fff;
            padding: 12px 26px;
            border-radius: 10px;
            font-weight: 600;
            font-size: .95rem;
            text-decoration: none;
            transition: background .2s, border-color .2s;
        }
        .btn-outline-white:hover { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.8); }

        /* ---- SECTION ---- */
        .section { padding: 80px 20px; }
        .section-alt { background: var(--card); }
        .container { max-width: 1140px; margin: 0 auto; }
        .section-title { font-size: 2rem; font-weight: 800; color: var(--text); text-align: center; letter-spacing: -.02em; margin-bottom: 10px; }
        .section-sub   { text-align: center; color: var(--muted); font-size: 1rem; max-width: 560px; margin: 0 auto 54px; }

        /* ---- FEATURES GRID ---- */
        .features { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 24px;
            transition: transform .25s, box-shadow .25s;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(99,102,241,.1); }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 1.4rem;
        }
        .feature-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; }
        .feature-card p  { font-size: .875rem; color: var(--muted); line-height: 1.6; }

        /* ---- THEMES DEMO ---- */
        .themes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
        .theme-card {
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
            text-decoration: none;
            display: block;
        }
        .theme-card:hover { transform: translateY(-4px); box-shadow: 0 12px 36px rgba(0,0,0,.12); }
        .theme-preview {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .theme-preview-inner {
            background: rgba(255,255,255,.95);
            border-radius: 8px;
            width: 100%;
            padding: 14px 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
        }
        .tp-label { font-size: 10px; font-weight: 700; color: #374151; margin-bottom: 4px; display: block; }
        .tp-input { width: 100%; height: 28px; border: 1.5px solid #e2e8f0; border-radius: 6px; background: #f8fafc; margin-bottom: 10px; }
        .tp-btn   { width: 80px; height: 28px; border-radius: 6px; display: block; }
        .theme-label { padding: 14px 16px; background: var(--card); }
        .theme-label strong { font-size: .9rem; font-weight: 700; display: block; }
        .theme-label span   { font-size: .78rem; color: var(--muted); }

        /* Light preview */
        .preview-light { background: linear-gradient(135deg, #f0f4ff, #e8f5e9); }
        .preview-light .tp-btn { background: #6366f1; }

        /* Dark preview */
        .preview-dark { background: #0f0f1a; }
        .preview-dark .theme-preview-inner { background: rgba(30,30,46,.95); border: 1px solid #3a3a58; }
        .preview-dark .tp-label { color: #cbd5e1; }
        .preview-dark .tp-input { background: #2a2a3e; border-color: #3a3a58; }
        .preview-dark .tp-btn   { background: #7c3aed; }
        .preview-dark + .theme-label { background: #1e1e2e; }
        .preview-dark + .theme-label strong { color: #e2e8f0; }
        .preview-dark + .theme-label span   { color: #64748b; }

        /* Material preview */
        .preview-material { background: linear-gradient(135deg, #e3f2fd, #fce4ec); }
        .preview-material .tp-input { border-top: none; border-left: none; border-right: none; border-radius: 0; border-color: #9e9e9e; background: transparent; }
        .preview-material .tp-btn   { background: #1976d2; border-radius: 4px; }

        /* Glass preview */
        .preview-glass {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        }
        .preview-glass .theme-preview-inner {
            background:      rgba(255,255,255,.12);
            backdrop-filter: blur(10px);
            border:          1px solid rgba(255,255,255,.2);
        }
        .preview-glass .tp-label { color: rgba(255,255,255,.85); }
        .preview-glass .tp-input { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.25); }
        .preview-glass .tp-btn   { background: rgba(255,255,255,.2); }

        /* ---- DEMO LINKS ---- */
        .demos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .demo-card {
            background: linear-gradient(135deg, var(--from), var(--to));
            border-radius: var(--radius);
            padding: 28px 24px;
            text-decoration: none;
            color: #fff;
            display: block;
            transition: transform .2s, box-shadow .2s;
        }
        .demo-card:hover { transform: translateY(-4px); box-shadow: 0 12px 36px rgba(0,0,0,.2); }
        .demo-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 6px; }
        .demo-card p  { font-size: .85rem; opacity: .85; margin-bottom: 18px; }
        .demo-card span { font-size: .82rem; font-weight: 600; border: 1.5px solid rgba(255,255,255,.5); border-radius: 99px; padding: 4px 14px; }

        /* ---- REQUIREMENTS ---- */
        .req-list { list-style: none; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .req-list li { display: flex; align-items: center; gap: 10px; font-size: .9rem; background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; font-weight: 500; }
        .req-list li::before { content: '✓'; color: #22c55e; font-weight: 800; font-size: 1rem; }

        /* ---- FOOTER ---- */
        footer { background: var(--dark); color: rgba(255,255,255,.5); text-align: center; padding: 28px 20px; font-size: .85rem; }
        footer strong { color: rgba(255,255,255,.9); }

        /* ---- Misc ---- */
        .pill {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 99px;
            font-size: .75rem;
            font-weight: 700;
            background: #e0e7ff;
            color: #4f46e5;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

<!-- =====================================================================
     HERO
===================================================================== -->
<section class="hero">
    <div class="container" style="position:relative;z-index:1">
        <div class="hero-badge">New in v1.0 &mdash; Multi-Step Forms + Conditional Logic</div>
        <h1>NexForm<br><span>Advanced Ajax Form Builder</span></h1>
        <p>A powerful, responsive PHP form builder with 4 stunning themes, multi-step wizards, conditional fields, drag-and-drop uploads, and a zero-config SQLite backend.</p>
        <div class="hero-cta">
            <a class="btn-white" href="examples/contact.php">View Live Demo</a>
            <a class="btn-outline-white" href="admin/">Admin Panel</a>
        </div>
    </div>
</section>

<!-- =====================================================================
     FEATURES
===================================================================== -->
<section class="section">
    <div class="container">
        <div class="pill">Features</div>
        <h2 class="section-title">Everything You Need</h2>
        <p class="section-sub">NexForm ships with a comprehensive feature set right out of the box.</p>

        <div class="features">

            <div class="feature-card">
                <div class="feature-icon" style="background:#ede9fe">🧙</div>
                <h3>Multi-Step Wizard Forms</h3>
                <p>Split long forms into digestible steps with animated progress bar and previous/next navigation.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#fef3c7">🎨</div>
                <h3>4 Beautiful Themes</h3>
                <p>Light, Dark, Material Design, and Glassmorphism – each fully customisable via CSS variables.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#dcfce7">👁</div>
                <h3>Conditional Field Logic</h3>
                <p>Show or hide fields dynamically based on the value of other fields. Zero configuration required.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#fce7f3">📁</div>
                <h3>Drag & Drop File Upload</h3>
                <p>Secure file upload with MIME validation, size limits, drag-and-drop UI and inline file preview.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#e0f2fe">🛡</div>
                <h3>3 Spam Protection Modes</h3>
                <p>Honeypot, reCAPTCHA v2 checkbox, and reCAPTCHA v3 invisible – switch with a single config change.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#fef2f2">⚡</div>
                <h3>Zero-Config SQLite Storage</h3>
                <p>Submissions logged automatically using SQLite – no database setup required. MySQL supported too.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#f0fdf4">📧</div>
                <h3>Email Notifications + Auto-Responder</h3>
                <p>HTML email notifications with file attachments, and auto-reply to the visitor via mail() or SMTP.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#fdf4ff">📊</div>
                <h3>Admin Panel + CSV Export</h3>
                <p>Password-protected admin panel to browse, search and delete submissions. Export any form to CSV.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#fff7ed">🔒</div>
                <h3>CSRF Protection & Rate Limiting</h3>
                <p>One-time tokens per submission prevent cross-site attacks. IP-based rate limiting blocks spam floods.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#f0f9ff">⭐</div>
                <h3>Rich Field Types</h3>
                <p>Text, email, tel, number, date, textarea, select, radio, checkbox, toggle, star rating, and file upload.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#fafaf9">🔗</div>
                <h3>Fluent PHP Builder API</h3>
                <p>Chain method calls to build forms in pure PHP – no HTML needed. Each field is fully typed and documented.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background:#ecfdf5">📱</div>
                <h3>Fully Responsive</h3>
                <p>Two-column grid layout collapses to single column on mobile. Tested on all modern browsers and devices.</p>
            </div>

        </div>
    </div>
</section>

<!-- =====================================================================
     THEMES
===================================================================== -->
<section class="section section-alt">
    <div class="container">
        <div class="pill">Themes</div>
        <h2 class="section-title">4 Stunning Themes</h2>
        <p class="section-sub">Switch themes with a single class change. Fully customisable via CSS custom properties.</p>

        <div class="themes-grid">

            <a href="examples/contact.php" class="theme-card">
                <div class="theme-preview preview-light">
                    <div class="theme-preview-inner">
                        <span class="tp-label">Your Name *</span>
                        <div class="tp-input"></div>
                        <span class="tp-label">Email Address *</span>
                        <div class="tp-input"></div>
                        <div class="tp-btn" style="background:#6366f1;border-radius:8px"></div>
                    </div>
                </div>
                <div class="theme-label">
                    <strong>Light Theme</strong>
                    <span>Clean, modern, default</span>
                </div>
            </a>

            <a href="examples/quote.php" class="theme-card">
                <div class="theme-preview preview-dark">
                    <div class="theme-preview-inner">
                        <span class="tp-label">Company Name *</span>
                        <div class="tp-input"></div>
                        <span class="tp-label">Email *</span>
                        <div class="tp-input"></div>
                        <div class="tp-btn" style="background:#7c3aed;border-radius:8px"></div>
                    </div>
                </div>
                <div class="theme-label" style="background:#1e1e2e">
                    <strong style="color:#e2e8f0">Dark Theme</strong>
                    <span style="color:#64748b">Sleek, professional</span>
                </div>
            </a>

            <a href="examples/registration.php" class="theme-card">
                <div class="theme-preview preview-material">
                    <div class="theme-preview-inner">
                        <span class="tp-label" style="color:#1976d2">First Name *</span>
                        <div class="tp-input"></div>
                        <span class="tp-label" style="color:#1976d2">Email *</span>
                        <div class="tp-input"></div>
                        <div class="tp-btn" style="background:#1976d2;border-radius:4px"></div>
                    </div>
                </div>
                <div class="theme-label">
                    <strong>Material Theme</strong>
                    <span>Google Material Design</span>
                </div>
            </a>

            <a href="examples/multistep.php" class="theme-card">
                <div class="theme-preview preview-glass">
                    <div class="theme-preview-inner">
                        <span class="tp-label">Full Name *</span>
                        <div class="tp-input" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3)"></div>
                        <span class="tp-label">Email *</span>
                        <div class="tp-input" style="background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3)"></div>
                        <div class="tp-btn" style="background:rgba(255,255,255,.25)"></div>
                    </div>
                </div>
                <div class="theme-label">
                    <strong>Glassmorphism Theme</strong>
                    <span>Frosted glass, modern</span>
                </div>
            </a>

        </div>
    </div>
</section>

<!-- =====================================================================
     LIVE DEMOS
===================================================================== -->
<section class="section">
    <div class="container">
        <div class="pill">Live Demos</div>
        <h2 class="section-title">Try the Forms</h2>
        <p class="section-sub">Four ready-to-use form types included. Each fully working and customisable.</p>

        <div class="demos-grid">
            <a class="demo-card" href="examples/contact.php" style="--from:#6366f1;--to:#8b5cf6">
                <h3>Contact Form</h3>
                <p>Name, email, subject, message, file upload. Light theme.</p>
                <span>View Demo &rarr;</span>
            </a>
            <a class="demo-card" href="examples/quote.php" style="--from:#1e1b4b;--to:#4c1d95">
                <h3>Quote Request</h3>
                <p>Business enquiry with service, budget and star rating. Dark theme.</p>
                <span>View Demo &rarr;</span>
            </a>
            <a class="demo-card" href="examples/registration.php" style="--from:#1565c0;--to:#0288d1">
                <h3>Registration Form</h3>
                <p>Account creation with plan selection and toggle switches. Material theme.</p>
                <span>View Demo &rarr;</span>
            </a>
            <a class="demo-card" href="examples/multistep.php" style="--from:#0f2027;--to:#2c5364">
                <h3>Multi-Step Wizard</h3>
                <p>3-step form with progress bar and glassmorphism design.</p>
                <span>View Demo &rarr;</span>
            </a>
        </div>
    </div>
</section>

<!-- =====================================================================
     REQUIREMENTS
===================================================================== -->
<section class="section section-alt">
    <div class="container">
        <div class="pill">Requirements</div>
        <h2 class="section-title">Minimal Requirements</h2>
        <p class="section-sub">NexForm works on virtually any PHP hosting environment.</p>
        <ul class="req-list">
            <li>PHP 8.1 or higher</li>
            <li>PDO extension (built-in)</li>
            <li>SQLite3 or MySQL</li>
            <li>FileInfo extension</li>
            <li>Apache or Nginx</li>
            <li>SMTP or mail() for email</li>
        </ul>
    </div>
</section>

<!-- =====================================================================
     INLINE DEMO FORM
===================================================================== -->
<section class="section">
    <div class="container" style="max-width:680px">
        <div class="pill">Live Preview</div>
        <h2 class="section-title">Try It Right Here</h2>
        <p class="section-sub">A working contact form below – enter details and hit Send.</p>

        <?php
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/nexform/autoload.php';
        use NexForm\NexForm;

        $inlineForm = new NexForm('contact');
        $inlineForm->action('process.php')
             ->theme('nf-theme-light')
             ->text('name',    'Your Name',     ['rules' => ['required'], 'placeholder' => 'John Smith', 'class' => 'nf-half'])
             ->email('email',  'Email Address',  ['rules' => ['required', 'email'], 'placeholder' => 'john@example.com', 'class' => 'nf-half'])
             ->select('subject', 'Subject', [
                 'general' => 'General Enquiry',
                 'support' => 'Technical Support',
                 'sales'   => 'Sales',
             ], ['rules' => ['required']])
             ->textarea('message', 'Message', ['rules' => ['required', 'minlen:10'], 'placeholder' => 'Your message here…', 'maxlen' => 1000])
             ->submit('Send Message');

        echo '<div class="nf-form-card">' . $inlineForm->render() . '</div>';
        ?>
    </div>
</section>

<footer>
    <strong>NexForm</strong> &mdash; Advanced Ajax PHP Form Builder &bull; Built with PHP 8 + Vanilla JS
</footer>

<script src="assets/js/nexform.js"></script>
<script>
    document.querySelectorAll('.nf-form').forEach(function (f) { new NexForm(f); });
</script>
</body>
</html>

<?php
session_start();
require_once 'alerto-db.php';

// Process logout if action=logout is passed
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: user-login.php");
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'admin', 'superadmin'])) {
    header("Location: user-login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: user-login.php");
    exit;
}

// Fetch active or archived/completed assistance request from database matching correct columns (resources, landmark)
// Fixed query logic to also check status so that 'archived' is treated like 'completed' / fully finished
$stmtReq = $pdo->prepare("
    SELECT * FROM assistance_requests 
    WHERE user_id = ? AND status NOT IN ('cancelled') 
    ORDER BY id DESC LIMIT 1
");
$stmtReq->execute([$user['id']]);
$activeRequest = $stmtReq->fetch(PDO::FETCH_ASSOC);

$initials = implode('', array_map(fn($n) => strtoupper($n[0] ?? ''), array_slice(explode(' ', $user['full_name']), 0, 2)));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard | ALERTO - CSU-Carig Student Council</title>

  <!-- Google Fonts: Poppins & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap"
    rel="stylesheet">

  <!-- Core Stylesheets -->
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/student.css">
</head>

<body>

  <div class="student-layout">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="studentSidebarBackdrop"></div>

    <!-- =================================================================
        CSU CRIMSON RED STUDENT SIDEBAR
        ================================================================= -->
    <aside class="student-sidebar" id="studentSidebar" aria-label="Student Navigation">

      <!-- Brand Header -->
      <a href="user-homepage.php" class="student-sidebar-brand">
        <div class="student-logo-box">
          <img src="logo/csulogo.png" alt="CSU Logo"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
          <span style="display: none; font-weight: 800; color: #ffffff;">A</span>
        </div>
        <div>
          <div class="student-brand-name">ALERTO</div>
          <div class="student-brand-sub">User Portal</div>
        </div>
      </a>

      <!-- Student Navigation Menu (Dashboard Only) -->
      <nav class="student-nav-menu">

        <!-- Dashboard -->
        <a href="user-homepage.php" class="student-nav-link active">
          <span class="student-nav-icon">
            <img src="icons/home.svg" alt=""
              onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" style="display:none;">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </span>
          <span>Dashboard</span>
        </a>

      </nav>

     <!-- Sidebar Footer -->
    <div class="student-sidebar-footer">
      <a href="user-homepage.php?action=logout" class="sidebar-logout-link">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          <span>Logout</span>
        </a>
      
      <span>&copy; 2026 CSU-COEA Student Council</span>
    </div>

    </aside>

    <!-- =================================================================
        MAIN CONTENT AREA
        ================================================================= -->
    <div class="student-main-content">

      <!-- Mobile Floating Menu Toggle -->
      <div class="student-mobile-bar"
        style="display: none; padding: 12px 18px; align-items: center; justify-content: space-between;">
        <button type="button" class="student-mobile-toggle-btn" id="studentSidebarMobileToggle"
          aria-label="Open navigation menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <span
          style="font-family: var(--heading-font); font-weight: 800; color: #700d23; font-size: 1.05rem;">ALERTO</span>
        <a href="user-homepage.php?action=logout" style="font-size: var(--fs-2xs); color: #700d23; font-weight: 700;">Logout</a>
      </div>

      <div class="student-content-body">

        <!-- =============================================================
             1. GCASH-STYLE TOP IDENTITY VERIFICATION CARD
             ============================================================= -->
        <section class="gcash-profile-card" aria-label="Student Identity Verification Details">
          <div class="gcash-card-inner">

            <!-- Left Info Lockup -->
            <div class="gcash-student-info-col">
              <div class="gcash-avatar-box">
                <span><?= htmlspecialchars($initials) ?></span>
              </div>
              <div class="gcash-text-meta">
                <h2>
                  <span><?= htmlspecialchars($user['full_name']) ?></span>
                  <span id="nameVerifiedCheck">
                    <svg style="width: 20px; height: 20px; fill: #38d39f;" viewBox="0 0 24 24">
                      <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>
                  </span>
                </h2>
                <div class="gcash-sub-meta">
                  <span>ID: <strong><?= htmlspecialchars($user['student_id']) ?></strong></span>
                  <span>&bull;</span>
                  <span><?= htmlspecialchars($user['program']) ?></span>
                  <span>&bull;</span>
                  <span><?= htmlspecialchars($user['year_level']) ?></span>
                </div>
                <div style="font-size: var(--fs-2xs); color: rgba(255,255,255,0.75); margin-top: 2px;">
                  <?= htmlspecialchars($user['email']) ?>
                </div>
              </div>
            </div>

            <!-- Right Verification Decision Badge -->
            <div class="gcash-status-badge-container">
              <span class="verification-decision-label">Identity Verification Status</span>
              <div id="verificationBadgeContainer">
                <span class="verification-badge approved" id="currentVerificationPill">
                  <img src="icons/check-badge.svg" alt="" style="width:14px;height:14px;"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round" style="display:none;width:14px;height:14px;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                  <span>Approved (Verified)</span>
                </span>
              </div>
            </div>

          </div>
        </section>

        <!-- Dynamic Locked Banner -->
        <div class="locked-overlay-card" id="verificationLockedBanner" style="display: none;">
          <div class="locked-icon-text">
            <div class="locked-icon-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" style="width: 20px; height: 20px;">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <div>
              <div class="locked-title" id="lockedBannerTitle">Verification Required</div>
              <div class="locked-desc" id="lockedBannerDesc">
                Your student profile must be Approved by the COEA Student Council before assistance requests can be
                submitted or tracked.
              </div>
            </div>
          </div>
          <a href="user-sign-in.php" class="unlock-action-btn">Re-upload Documents &rarr;</a>
        </div>

        <!-- =============================================================
             2. REQUEST STATUS UPDATE (DYNAMIC TRACKER OR EMPTY STATE)
             ============================================================= -->
        <section class="tracker-section-card" id="requestsTrackSection"
          aria-label="Assistance Request Progress Tracker">

          <div class="tracker-card-header">
            <div>
              <h3>Request Status Update</h3>
              <p>Live progress breakdown for your active disaster relief request.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
              <?php 
                $isArchivedOrCompleted = $activeRequest && in_array(strtolower(trim($activeRequest['status'] ?? '')), ['completed', 'archived']);
              ?>
              <?php if (!$activeRequest || $isArchivedOrCompleted): ?>
                <button type="button" class="unlock-action-btn" onclick="handleRequestAssistanceClick()"
                  style="background: #700d23; box-shadow: 0 4px 12px rgba(112, 13, 35, 0.2); border: none; cursor: pointer;">
                  <span>+ Request Assistance</span>
                </button>
              <?php else: ?>
                <a href="user-request.php" class="unlock-action-btn"
                  style="background: #700d23; box-shadow: 0 4px 12px rgba(112, 13, 35, 0.2);">
                  <span>Update Request</span>
                </a>
                <span class="request-id-tag" id="activeRequestIdTag"><?= htmlspecialchars($activeRequest['request_code'] ?? 'REQ-' . str_pad($activeRequest['id'], 5, '0', STR_PAD_LEFT)) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!$activeRequest): ?>
            <!-- Empty State when no active request exists -->
            <div style="text-align: center; padding: 40px 20px; background: #faf7f8; border-radius: var(--radius-md); border: 1px dashed rgba(112, 13, 35, 0.2);">
              <h4 style="color: #700d23; margin-bottom: 8px; font-family: var(--heading-font);">No Active Relief Requests</h4>
              <p style="color: var(--admin-muted); font-size: var(--fs-xs); margin-bottom: 20px;">You currently have no active disaster relief requests submitted.</p>
              <button type="button" class="unlock-action-btn" onclick="handleRequestAssistanceClick()"
                style="background: #700d23; box-shadow: 0 4px 12px rgba(112, 13, 35, 0.2); border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <span>Request Assistance Now</span>
              </button>
            </div>
          <?php else: 
            $status = strtolower(trim($activeRequest['status'] ?? 'pending'));
            // Treat archived exactly like completed
            if ($status === 'archived') {
                $status = 'completed';
            }

            $s1 = 'completed';
            $s2 = in_array($status, ['approved', 'in_progress', 'completed']) ? 'completed' : ($status == 'pending' ? 'active' : '');
            $s3 = in_array($status, ['in_progress', 'completed']) ? 'completed' : ($status == 'approved' ? 'active' : '');
            $s4 = ($status == 'completed') ? 'completed' : ($status == 'in_progress' ? 'active' : '');

            $submittedTimestamp = $activeRequest['submitted_at'] ?? $activeRequest['created_at'] ?? null;
            $formattedDate = $submittedTimestamp ? date('M j, H:i', strtotime($submittedTimestamp)) : 'Recent';
          ?>
            <!-- Stepped Progress Bar -->
            <div class="stepper-track-container">
              <div class="stepper-steps-wrapper">

                <!-- Step 1: Pending Request -->
                <div class="stepper-step <?= $s1 ?>" id="step1">
                  <div class="step-node-circle">
                    <img src="icons/doc-check.svg" alt="" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                      <polyline points="14 2 14 8 20 8"></polyline>
                      <line x1="16" y1="13" x2="8" y2="13"></line>
                      <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                  </div>
                  <div class="step-text-title">Pending Request</div>
                  <div class="step-text-time"><?= $formattedDate ?></div>
                </div>

                <!-- Step 2: Approved -->
                <div class="stepper-step <?= $s2 ?>" id="step2">
                  <div class="step-node-circle">
                    <img src="icons/approved-check.svg" alt="" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                  </div>
                  <div class="step-text-title">Approved</div>
                  <div class="step-text-time"><?= in_array($status, ['approved', 'in_progress', 'completed']) ? 'Verified' : 'Pending Review' ?></div>
                </div>

                <!-- Step 3: In Progress -->
                <div class="stepper-step <?= $s3 ?>" id="step3">
                  <div class="step-node-circle">
                    <img src="icons/truck.svg" alt="" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                      <rect x="1" y="3" width="15" height="13"></rect>
                      <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                      <circle cx="5.5" cy="18.5" r="2.5"></circle>
                      <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                  </div>
                  <div class="step-text-title">In Progress</div>
                  <div class="step-text-time"><?= $status == 'in_progress' || $status == 'completed' ? 'Dispatched' : 'Awaiting Dispatch' ?></div>
                </div>

                <!-- Step 4: Completed -->
                <div class="stepper-step <?= $s4 ?>" id="step4">
                  <div class="step-node-circle">
                    <img src="icons/handshake.svg" alt="" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </div>
                  <div class="step-text-title">Completed</div>
                  <div class="step-text-time"><?= $status == 'completed' ? 'Delivered' : 'Pending Delivery' ?></div>
                </div>

              </div>
            </div>

            <!-- Active Request Details Card (mapped to 'resources' and 'landmark') -->
            <div
              style="background: #faf7f8; border-radius: var(--radius-md); padding: var(--space-5) var(--space-6); border: 1px solid rgba(112, 13, 35, 0.1); display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); font-size: var(--fs-xs);">
              <div>
                <span style="color: var(--admin-muted); display: block; margin-bottom: 2px;">Assistance Package:</span>
                <strong style="color: #700d23; font-size: var(--fs-sm);"><?= htmlspecialchars($activeRequest['resources'] ?? 'N/A') ?></strong>
              </div>
              <div>
                <span style="color: var(--admin-muted); display: block; margin-bottom: 2px;">Assigned Location:</span>
                <strong style="color: var(--admin-text);"><?= htmlspecialchars($activeRequest['landmark'] ?? 'N/A') ?></strong>
              </div>
            </div>
          <?php endif; ?>

        </section>

      </div>
    </div>

  </div>

  <script>
    // Toggle Mobile Sidebar
    const mobileToggle = document.getElementById('studentSidebarMobileToggle');
    const sidebar = document.getElementById('studentSidebar');
    const backdrop = document.getElementById('studentSidebarBackdrop');

    if (mobileToggle && sidebar && backdrop) {
      mobileToggle.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-open');
        backdrop.classList.toggle('active');
      });
      backdrop.addEventListener('click', () => {
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.remove('active');
      });
    }

    // Dynamic State Switcher for Verification Decisions & Gating
    let currentDecision = '<?= htmlspecialchars($user['status'] ?? 'approved') ?>';

    function setVerificationState(state) {
      currentDecision = state;
      const pill = document.getElementById('currentVerificationPill');
      const nameCheck = document.getElementById('nameVerifiedCheck');
      const lockedBanner = document.getElementById('verificationLockedBanner');
      const trackerSection = document.getElementById('requestsTrackSection');
      const bannerTitle = document.getElementById('lockedBannerTitle');
      const bannerDesc = document.getElementById('lockedBannerDesc');

      pill.className = `verification-badge ${state}`;

      if (state === 'approved' || state === 'verified') {
        pill.innerHTML = `
          <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Approved (Verified)</span>
        `;
        nameCheck.style.display = 'inline-block';
        lockedBanner.style.display = 'none';
        trackerSection.classList.remove('locked');
      }
      else if (state === 'pending' || state === 'unverified') {
        pill.innerHTML = `
          <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 14 14"></polyline>
          </svg>
          <span>Pending Verification</span>
        `;
        nameCheck.style.display = 'none';
        bannerTitle.textContent = 'Verification in Progress';
        bannerDesc.textContent = 'Your documents have been submitted and are waiting for review by the COEA Student Council.';
        lockedBanner.style.display = 'flex';
        trackerSection.classList.add('locked');
      }
      else if (state === 'rejected') {
        pill.innerHTML = `
          <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
          <span>Verification Rejected</span>
        `;
        nameCheck.style.display = 'none';
        bannerTitle.textContent = 'Verification Rejected';
        bannerDesc.textContent = 'Your uploaded identification or assessment document was unclear or unverified. Please re-upload valid credentials.';
        lockedBanner.style.display = 'flex';
        trackerSection.classList.add('locked');
      }
      else if (state === 'banned') {
        pill.innerHTML = `
          <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
          </svg>
          <span>Account Restricted (Banned)</span>
        `;
        nameCheck.style.display = 'none';
        bannerTitle.textContent = 'Account Restricted';
        bannerDesc.textContent = 'This student profile has been restricted from emergency assistance coordination due to administrative policy.';
        lockedBanner.style.display = 'flex';
        trackerSection.classList.add('locked');
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      setVerificationState(currentDecision);
    });

    // Request Assistance Click Guard
    function handleRequestAssistanceClick() {
      if (currentDecision !== 'approved' && currentDecision !== 'verified') {
        alert('Action Locked: You must have an Approved student profile to file an assistance request.');
      } else {
        window.location.href = 'user-request.php';
      }
    }
  </script>
</body>

</html>
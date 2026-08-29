<?php

/**
  * @var string $color
  * @var string $bgcolor
  */
?>/* sidebar */

/* --- Sidebar ------------------------------------------------------------- */

#main-sidebar {
  --lte-sidebar-bg: <?= $bgcolor ?>;
  background-color: var(--lte-sidebar-bg) !important;
}

#main-sidebar .sidebar-brand {
  text-decoration: none;
  border-bottom: 1px solid <?= $color ?>;
}

/* Allow long brand text to wrap instead of being clipped (v4 selector) */
.app-sidebar .brand-link {
  height: auto;        /* remove fixed height */
  line-height: 1.2;
  padding-top: .75rem;
  padding-bottom: .75rem;
  white-space: normal; /* allow wrapping */
}

/* Ensure the brand text itself can break long words/URLs */
.app-sidebar .brand-link .brand-text {
  white-space: normal;
  word-break: break-word;
  overflow: visible;
  display: inline;
}

/* Optional: respect theme variables when data-bs-theme="dark" is set */
.app-sidebar[data-bs-theme="dark"] .sidebar-brand .brand-link {
  border-bottom-color: var(--bs-border-color);
}

/* --- Buttons ------------------------------------------------------------- */

.btn-outline-secondary {
  --bs-btn-color: #333;
}

.btn:focus,
.btn:focus-visible {
  outline: none; /* remove default outline */
  box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.75) !important; /* stronger focus ring */
}

/* --- Links --------------------------------------------------------------- */

/* Form helper links */
.content .form-text a {
  color: #0d6edd; /* matches BS primary tone */
}

/* Navbar links (check contrast on your header bg) */
.navbar-light .navbar-nav .nav-link {
  color: #666;
}

/* Pagination active */
.page-item.active .page-link {
  background-color: #0d6edd;
}

/* --- Footer -------------------------------------------------------------- */

.app-footer {
  color: #666;
}

/* --- Alerts -------------------------------------------------------------- */

.alert a:hover {
  text-decoration: none !important;
}

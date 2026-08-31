<?php

/**
  * @var string $color
  * @var string $bgcolor
  */
?>/* Kontiki administration shell */

.kontiki-icon-sprite {
  display: none;
}

.kontiki-icon {
  display: inline-block;
  width: 1em;
  height: 1em;
  fill: currentColor;
  vertical-align: -0.125em;
}

.file-download-icon .kontiki-icon {
  width: 1em;
  height: 1em;
}

.kontiki-admin-page {
  --kontiki-sidebar-width: 16rem;
  min-height: 100vh;
  margin: 0;
  background: var(--bs-tertiary-bg);
}

.kontiki-shell {
  display: grid;
  grid-template-columns: var(--kontiki-sidebar-width) minmax(0, 1fr);
  grid-template-rows: auto 1fr auto;
  min-height: 100vh;
}

.kontiki-header {
  position: sticky;
  z-index: 1020;
  top: 0;
  grid-column: 2;
  min-height: 3.5rem;
  padding: .25rem 1rem;
  border-bottom: 1px solid var(--bs-border-color);
}

.kontiki-main {
  grid-column: 2;
  min-width: 0;
  background: var(--bs-body-bg);
}

.kontiki-footer {
  grid-column: 2;
  padding: 1rem;
  color: #666;
  background: var(--bs-body-bg);
  border-top: 1px solid var(--bs-border-color);
}

/* --- Sidebar ------------------------------------------------------------- */

.kontiki-sidebar {
  position: fixed;
  z-index: 1030;
  inset: 0 auto 0 0;
  width: var(--kontiki-sidebar-width);
  overflow-y: auto;
  color: <?= $color ?>;
  background-color: <?= $bgcolor ?>;
  transition: transform .2s ease-in-out;
}

.kontiki-sidebar-brand {
  border-bottom: 1px solid var(--bs-border-color);
}

.kontiki-brand-link {
  display: block;
  padding: .9rem 1rem;
  color: inherit;
  line-height: 1.2;
  text-decoration: none;
  white-space: normal;
}

.kontiki-brand-link:hover,
.kontiki-brand-link:focus-visible {
  color: inherit;
  background: rgb(255 255 255 / 10%);
}

.kontiki-brand-text {
  display: inline;
  overflow: visible;
  white-space: normal;
  overflow-wrap: anywhere;
}

.kontiki-sidebar-content {
  padding: .5rem;
}

.kontiki-sidebar-menu,
.kontiki-submenu {
  gap: .15rem;
}

.kontiki-sidebar .nav-link {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .65rem .75rem;
  color: inherit;
  text-align: left;
  border: 0;
  border-radius: var(--bs-border-radius);
  background: transparent;
}

.kontiki-sidebar .nav-link:hover,
.kontiki-sidebar .nav-link:focus-visible,
.kontiki-sidebar .nav-link.active {
  color: inherit;
  background: rgb(255 255 255 / 14%);
}

.kontiki-sidebar .nav-link p {
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: space-between;
  margin: 0;
}

.kontiki-sidebar .nav-icon,
.kontiki-sidebar .nav-arrow {
  flex: 0 0 auto;
}

.kontiki-sidebar .nav-arrow {
  transition: transform .2s ease-in-out;
}

.kontiki-sidebar [aria-expanded="true"] .nav-arrow {
  transform: rotate(-90deg);
}

.kontiki-submenu {
  padding: .15rem 0 .25rem 1.7rem;
}

.kontiki-submenu[hidden] {
  display: none;
}

.kontiki-sidebar-backdrop {
  display: none;
}

body.sidebar-collapsed .kontiki-shell {
  grid-template-columns: 0 minmax(0, 1fr);
}

body.sidebar-collapsed .kontiki-sidebar {
  transform: translateX(-100%);
}

/* --- Login --------------------------------------------------------------- */

.kontiki-login-page {
  min-height: 100vh;
  margin: 0;
}

.kontiki-login-main {
  display: grid;
  min-height: 100vh;
  place-items: center;
  padding-block: 2rem;
}

.kontiki-login {
  width: min(100%, 26rem);
}

.kontiki-login-logo {
  margin-bottom: 1rem;
  font-size: 1.5rem;
  text-align: center;
  overflow-wrap: anywhere;
}

@media (max-width: 991.98px) {
  .kontiki-shell {
    grid-template-columns: minmax(0, 1fr);
  }

  .kontiki-header,
  .kontiki-main,
  .kontiki-footer {
    grid-column: 1;
  }

  .kontiki-sidebar {
    width: min(85vw, var(--kontiki-sidebar-width));
    transform: translateX(-100%);
  }

  body.sidebar-open {
    overflow: hidden;
  }

  body.sidebar-open .kontiki-sidebar {
    transform: translateX(0);
  }

  body.sidebar-open .kontiki-sidebar-backdrop {
    position: fixed;
    z-index: 1025;
    inset: 0;
    display: block;
    width: 100%;
    height: 100%;
    padding: 0;
    border: 0;
    background: rgb(0 0 0 / 45%);
  }
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

/* --- Alerts -------------------------------------------------------------- */

.alert a:hover {
  text-decoration: none !important;
}

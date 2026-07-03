<?php

return [

    // ════════════════════════════════════════════════════════
    // NAVBAR
    // ════════════════════════════════════════════════════════
    'nav_home'          => 'Home',
    'nav_infos'         => 'Info',
    'nav_news'          => 'News',
    'nav_contact'       => 'Contact',
    'nav_dashboard'     => 'Anomaly Register',
    'nav_manage_users'  => 'User Management',
    'nav_logout'        => 'Logout',
    'nav_login'         => 'Login',
    'nav_chantiers'     => 'Worksites',

    // Dropdown "Anomaly Board"
    'nav_tableau_label'   => 'Anomaly Board',
    'nav_tab_engineering' => 'Engineering',
    'nav_tab_development' => 'Development',
    'nav_tab_precom'      => 'Pre-commissioning / Commissioning',
    'nav_tab_operations'  => 'Exploitation / Operations',
    'nav_tab_support'     => 'Support Services & Additional Services',

    // ════════════════════════════════════════════════════════
    // AUTHENTICATION
    // ════════════════════════════════════════════════════════
    'auth_username'        => 'Username',
    'auth_password'        => 'Password',
    'auth_remember'        => 'Remember me',
    'auth_sign_in'         => 'Sign in',
    'auth_forgot_password' => 'Forgot your password?',
    'auth_forgot_desc'     => 'Forgot your password? No problem. Enter your email address and we will send you a reset link.',
    'auth_send_reset_link' => 'Send Reset Link',

    // ════════════════════════════════════════════════════════
    // BUTTONS & COMMON
    // ════════════════════════════════════════════════════════
    'btn_back'           => '← Back',
    'btn_save'           => 'Save',
    'btn_cancel'         => 'Cancel',
    'btn_create'         => 'Create',
    'btn_edit'           => 'Edit',
    'btn_delete'         => 'Delete',
    'btn_view'           => 'View',
    'btn_add'            => '➕ Add',
    'select_placeholder' => '— Select —',

    // ════════════════════════════════════════════════════════
    // HOME
    // ════════════════════════════════════════════════════════
    'home_welcome' => 'Welcome to MGDS — always one step ahead',

    // ════════════════════════════════════════════════════════
    // ANOMALIES (incidents)
    // ════════════════════════════════════════════════════════
    'incidents_title'         => 'Anomalies',
    'incident_new'            => 'New Anomaly',
    'incident_add'            => '+ Add Anomaly',
    'incident_edit_title'     => 'Edit Anomaly #:id',
    'incident_confirm_delete' => 'Delete this anomaly?',
    'incident_none'           => 'No anomalies recorded.',
    'incident_create_btn'     => 'Create Anomaly',
    'incident_save_btn'       => 'Save',
    'incident_closed_warning' => 'This anomaly is <strong>closed</strong> and can no longer be edited.',
    'incident_issued_by'      => 'Issued by',

    // Table columns
    'col_id'           => 'Ref.',
    'col_issued_on'    => 'Issued on',
    'col_photo_open'   => 'Open photo',
    'col_photo_closed' => 'Closed photo',
    'col_closed_on'    => 'Closed on',
    'col_discipline'   => 'Discipline',
    'col_status'       => 'Status',
    'col_actions'      => 'Actions',

    // Statuses
    'status_na'          => '⬛ N/A',
    'status_open'        => '🟥 Open',
    'status_in_progress' => '🟧 In progress',
    'status_closed'      => '🟩 Closed',

    // Detail sections
    'section_general_info' => 'General Information',
    'section_tracking'     => 'Tracking',
    'section_description'  => 'Description & Remarks',
    'section_qfc'          => 'QFC',
    'section_photos'       => 'Photos',

    // Form fields
    'field_discipline'      => 'Discipline',
    'field_system'          => 'System',
    'field_work_lot'        => 'Work Package',
    'field_zone'            => 'Zone',
    'field_chantier'        => 'Worksite',
    'field_label'           => 'Tag / Label',
    'field_category'        => 'Category',
    'field_internal'        => 'Internal',
    'field_responsibility'  => 'Responsibility',
    'field_status'          => 'Status',
    'field_issued_on'       => 'Issued on',
    'field_updated_on'      => 'Updated on',
    'field_closed_on'       => 'Closed on',
    'field_planned_closure' => 'Planned closure',
    'field_closure_date'    => 'Closure date',
    'field_qfc_open'        => 'Open n°',
    'field_qfc_closed'      => 'Closed n°',
    'field_qfc_open_form'   => 'QFC open n°',
    'field_qfc_closed_form' => 'QFC closed n°',
    'field_description'     => 'Description & Remarks',
    'field_photo_open'      => 'Open photo',
    'field_photo_closed'    => 'Closed photo',

    'photo_sets_issue_date'  => '(automatically sets the issue date)',
    'photo_sets_update_date' => '(automatically sets the update date)',
    'photo_delete'           => '🗑 Remove photo',
    'photo_take'             => 'Take a photo',
    'photo_gallery'          => 'Choose from gallery',

    'form_incident_closed' => 'Anomaly <strong>closed</strong> — only the status can be changed.',
    'form_manage_zones'    => 'Manage zones',

    // ════════════════════════════════════════════════════════
    // SIDEBAR
    // ════════════════════════════════════════════════════════
    'sidebar_incidents'    => 'Anomalies',
    'sidebar_new_incident' => 'New Anomaly',
    'sidebar_manage_zones' => 'Manage Zones',
    'sidebar_add_incident' => 'Add Anomaly',

    // ════════════════════════════════════════════════════════
    // SEARCH
    // ════════════════════════════════════════════════════════
    'search_placeholder' => 'Search by worksite or location...',
    'search_label'       => 'Search',
    'search_active'      => 'Active filter:',
    'search_clear'       => 'Clear',

    // ════════════════════════════════════════════════════════
    // ZONES
    // ════════════════════════════════════════════════════════
    'zones_title' => 'Zone Management',
    'zone_new'    => 'New Zone',
    'zone_name'   => 'Zone name',
    'zone_none'   => 'No zones recorded.',

    // ════════════════════════════════════════════════════════
    // USERS
    // ════════════════════════════════════════════════════════
    'users_title'            => 'User Management',
    'user_add'               => '➕ Add',
    'user_add_title'         => '➕ Add User',
    'user_edit_title'        => '✏️ Edit User',
    'col_username'           => 'Username',
    'col_email'              => 'Email',
    'col_role'               => 'Role',
    'user_email_hint'        => 'Email (for password reset)',
    'user_password'          => 'Password',
    'user_password_optional' => 'Password (leave blank to keep current)',
    'user_role'              => 'Role',
    'user_save'              => '💾 Save',
    'user_create'            => '💾 Create',

    // ════════════════════════════════════════════════════════
    // WORKSITES (chantiers)
    // ════════════════════════════════════════════════════════
    'chantiers_title'      => 'Worksite Management',
    'chantier_new'         => '➕ New Worksite',
    'chantier_add_title'   => '➕ Add Worksite',
    'chantier_edit_title'  => '✏️ Edit Worksite',
    'chantier_created'     => 'Worksite created successfully.',
    'chantier_updated'     => 'Worksite updated successfully.',
    'chantier_deleted'     => 'Worksite deleted.',
    'chantier_none'        => 'No worksites recorded.',
    'col_chantier'         => 'Worksite',
    'col_localite'         => 'Location',
    'col_incidents_count'  => 'Anomalies',
    'field_nom'            => 'Name',
    'field_localite'       => 'Location',

    // ════════════════════════════════════════════════════════
    // PUBLIC PAGES & MISC
    // ════════════════════════════════════════════════════════
    'home_title'       => 'Welcome to MGDS — always one step ahead',
    'infos_title'      => 'Information',
    'infos_p1'         => 'MGDS is coming soon 🚀',
    'infos_p2'         => 'Our platform is currently being finalised to offer you the best possible experience.',
    'infos_p3'         => 'Thank you for your patience — the official launch is just around the corner. The launch date will be announced very soon.',
    'infos_p4'         => 'The MGDS team thanks you for your patience and understanding.',
    'contact_title'    => 'Contact',
    'contact_coming'   => 'Contact page coming soon.',
    'news_title'       => "What's New",
    'news_coming_p1'   => 'This page will be available soon.',
    'news_coming_p2'   => 'The MGDS team thanks you for your patience and understanding.',
    'footer_rights'    => '© :year MGDS — All rights reserved',

    // Forced password change
    'force_change_title'       => 'Password Change Required',
    'force_change_subtitle'    => 'For security reasons, you must set a new password before continuing.',
    'force_change_new_password'=> 'New password',
    'force_change_confirm'     => 'Confirm password',
    'force_change_btn'         => 'Save and continue',
    'password_changed_success' => 'Password updated successfully. Welcome!',

    // Credentials PDF
    'btn_courrier'             => 'Download credentials letter (PDF)',
    'pdf_download'     => '📄 Download PDF',

    // ── Zones & Chantier show
    'zone_add_section' => 'Add a zone',
    'zone_existing' => 'Existing zones',
    'zone_confirm_delete' => 'Delete zone \":name\"?',
    'kpi_total' => 'Total anomalies',
    'kpi_open' => 'In progress / open',
    'kpi_closed' => 'Closed',
    'kpi_closure_rate' => 'Closure rate',
    'chart_by_status' => 'Breakdown by status',
    'chantier_anomalies' => 'Worksite anomalies',
    // ── Tarifs & Achat
    'nav_buy' => 'Buy',
    'pricing_title' => 'Our Plans',
    'pricing_subtitle' => 'Choose the plan suited to your project',
    'pricing_monthly' => 'Monthly',
    'pricing_annual' => 'Annual',
    'pricing_save' => 'Save 20%',
    'pricing_persons' => 'Number of users',
    'pricing_support' => '24/7 Technical support',
    'pricing_access' => 'Anomaly Board Access',
    'pricing_contact_btn' => 'Contact us',
    'pricing_choose' => 'Choose this plan',
    'pricing_included' => 'Included',
    'pricing_not_access' => 'You do not have access to this feature. Upgrade to a higher plan.',
    'pricing_upgrade' => 'View plans',
    // ── Membres chantier
    'chantier_name_taken' => 'This worksite name is already taken, please choose a new one.',
    'chantier_members' => 'Worksite members',
    'chantier_add_member' => 'Add a user',
    'chantier_user_added' => 'User added successfully.',
    'chantier_user_updated' => 'Role updated.',
    'chantier_user_removed' => 'User removed.',
    'chantier_user_already' => 'This user is already a member of this worksite.',
    'chantier_creator_no_remove' => 'The creator cannot be removed from the worksite.',
    'chantier_role' => 'Role on worksite',
    'chantier_member_search' => 'Search for a user...',
    'col_member' => 'Member',
    'col_role_chantier' => 'Role on worksite',
    'col_creator' => 'Creator',
    // ── Suppression utilisateur
    'user_deleted'=>'User deleted and removed from all worksites.',
    'user_cannot_delete_self'=>'You cannot delete your own account.',
    'user_search_placeholder'=>'Search by username, email or role...',
    'user_none'=>'No user found.',
];

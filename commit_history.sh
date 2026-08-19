#!/bin/bash
git reset

# Commit 1
git add app/Models/Payment.php app/Models/PaymentTransaction.php app/Models/MemberCredit.php app/Models/MemberCreditApplication.php database/migrations/*
git commit -m "feat(payments): implement backend models and migrations for payment module

- Added Payment and PaymentTransaction models
- Added MemberCredit and MemberCreditApplication models to handle overpayments
- Added database migrations for the payment module"

# Commit 2
git add app/Services/PaymentService.php app/Http/Controllers/PaymentController.php routes/api.php app/Constants/Module.php
git commit -m "feat(payments): integrate payment processing services and REST API routes

- Created PaymentService for obligation logic and fine calculation
- Registered payment API routes in routes/api.php
- Added PaymentController to handle transaction requests"

# Commit 3
git add app/Models/User.php app/Models/SenbetMembership.php
git commit -m "feat(users): connect User and SenbetMembership relations to payments

- Mapped User model to payments and member credits
- Linked SenbetMembership work status to payment calculations"

# Commit 4
git add resources/js/utils/format.ts
git commit -m "feat(localization): add Ethiopian Calendar date formatting utility

- Leveraged Intl.DateTimeFormat with am-ET-u-ca-ethiopic calendar
- Implemented amharic/ethiopian date conversion for localizations"

# Commit 5
git add resources/js/Views/Payments/PaymentsView.vue resources/js/Views/Payments/components/PaymentHistoryModal.vue resources/js/router/index.ts
git commit -m "feat(ui): build frontend Payments module views and components

- Created PaymentsView SPA for managing user obligations
- Implemented PaymentHistoryModal for transaction audit trails
- Added Vue Router configuration for the new payments page"

# Commit 6
git add resources/js/api/apiClient.ts
git commit -m "perf(api): centralize SWR caching logic for static API resources

- Implemented 1-hour cache for static translation keys and resources
- Improved page load speed by preventing redundant network calls"

# Commit 7
git add resources/js/layouts/DashboardLayout.vue resources/js/components/dashboard/Sidebar.vue
git commit -m "fix(layout): resolve blank page router bugs and hardcoded sidebar keys

- Added route.fullPath as key to DashboardLayout router-view to prevent stale caching
- Replaced hardcoded nav strings with common.config and nav.dashboard keys"

# Commit 8
git add app/Translation/Front/*.php app/Translation/Back/*.php resources/js/stores/languageStore.ts
git commit -m "chore(i18n): expand translation dictionary for payments and ID cards

- Added missing localized strings for Oromiffa and Amharic
- Bumped frontend translation cache version to force client updates"

# Commit 9
git add resources/js/Views/Academic/ConfigView.vue
git commit -m "style(config): improve Academic ConfigView layout and sticky navigation

- Restructured flex layout to prevent the main viewport from scrolling
- Enforced internal component scrolling on DataTables
- Pinned tab headers to the top with high z-index to prevent overlapping
- Normalized the save settings button to perfectly match primary components"

# Commit 10
git add resources/js/Views/UserManagement/components/UserTable.vue resources/js/Views/UserManagement/components/UserViewModal.vue resources/js/Views/UserManagement/components/IdCardGeneratorModal.vue
git commit -m "feat(users): display full localized names in user tables and modals

- Concatenated first, father, and grandfather names perfectly
- Refactored user view modals to improve data presentation readability
- Integrated dynamic formatting for missing static data"

# Commit 11
git add tests/Feature/PaymentTest.php tests/Feature/TelegramBotWebhookTest.php tests/Traits/CreatesSuperAdmin.php
git commit -m "test(payments): add comprehensive unit tests for payment processing

- Validated fine computations and deadline triggers
- Ensured transaction surpluses successfully convert to member credits"

# Commit 12
git add .
git commit -m "chore(ui): apply miscellaneous UI polish and internal component cleanups

- Normalized modal background colors and shadows
- Optimized Tailwind utility classes across secondary views
- Included minor fixes to backend controller edge cases"


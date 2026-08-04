#!/bin/bash
git config user.email "bot@senbetsystem.com"
git config user.name "Antigravity Bot"

git add config/app.php vite.config.js tsconfig.json composer.json package.json 2>/dev/null
git commit -m "chore: rename project to Senbet System and update configs" || true

git add app/Models/BotNotification.php app/Models/Feedback.php app/Models/FeedbackNote.php app/Models/FeedbackReply.php app/Models/NotificationDeliveryLog.php app/Models/Setting.php 2>/dev/null
git commit -m "feat(models): add feedback and notification models" || true

git add app/Models/User.php app/Models/UserInfo.php app/Models/TelegramUser.php 2>/dev/null
git commit -m "refactor(models): update user and profile models to registration_id" || true

git add app/Providers/RepositoryServiceProvider.php bootstrap/providers.php 2>/dev/null
git commit -m "feat(providers): register postgres repository service providers" || true

git add database/migrations/ 2>/dev/null
git commit -m "feat(db): clean and add missing feedback migrations" || true

git add database/seeders/ 2>/dev/null
git commit -m "chore(db): update role and user seeders for new schema" || true

git add app/Repositories/ 2>/dev/null
git commit -m "feat(repositories): implement postgres repositories and replace firestore" || true

git add app/Services/ 2>/dev/null
git commit -m "refactor(services): update services to use postgres repositories" || true

git add app/Http/Controllers/ 2>/dev/null
git commit -m "refactor(controllers): update controllers for new schema and services" || true

git add app/Http/Requests/ 2>/dev/null
git commit -m "refactor(requests): update validation rules for registration_id" || true

git add app/Translation/ 2>/dev/null
git commit -m "feat(translations): update language keys for new schema and naming" || true

git add routes/ 2>/dev/null
git commit -m "refactor(routes): update web and api routes" || true

git add resources/views/ 2>/dev/null
git commit -m "feat(views): add email layout and borrow templates" || true

git add resources/js/bootstrap.ts resources/js/app.ts resources/js/api/ 2>/dev/null
git commit -m "feat(vue): migrate setup to typescript and add api clients" || true

git add resources/js/stores/ 2>/dev/null
git commit -m "feat(vue): implement pinia stores for state management" || true

git add resources/js/types/ resources/js/utils/ resources/js/constants/ resources/js/composables/ resources/js/bot_enums.ts 2>/dev/null
git commit -m "feat(vue): add typescript interfaces, composables, and utilities" || true

git add resources/js/components/common/Form* 2>/dev/null
git commit -m "feat(vue): create common form components" || true

git add resources/js/components/common/Table* resources/js/components/common/Data* 2>/dev/null
git commit -m "feat(vue): create data grid and table components" || true

git add resources/js/components/common/ resources/js/components/InputError.vue resources/js/components/ToastNotification.vue resources/js/components/animations/ 2>/dev/null
git commit -m "feat(vue): create generic UI elements and alerts" || true

git add resources/js/layouts/ resources/js/components/navigation/ resources/js/components/dashboard/ resources/js/components/public/ resources/js/components/Navbar.vue 2>/dev/null
git commit -m "feat(vue): implement dashboard layouts and navigation components" || true

git add resources/js/Views/Dashboard/ resources/js/Views/Profile/ 2>/dev/null
git commit -m "feat(vue): implement dashboard overview and profile views" || true

git add resources/js/Views/UserManagement/ 2>/dev/null
git commit -m "feat(vue): implement advanced user management interface" || true

git add resources/js/Views/Public/ resources/js/Views/Auth/ resources/js/views/ForgotPasswordView.vue 2>/dev/null
git commit -m "feat(vue): implement public pages and authentication flows" || true

git add resources/js/views/bot/ 2>/dev/null
git commit -m "feat(vue): implement bot administration views" || true

git add resources/js/router/ 2>/dev/null
git commit -m "feat(vue): implement vue router with auth guards" || true

# Clean up explicitly deleted files
git rm resources/js/app.js resources/js/components/AppLayout.vue resources/js/firebase.js resources/js/views/FeedbackView.vue resources/js/views/LoginView.vue resources/js/views/ResetPasswordView.vue 2>/dev/null || true
git commit -m "chore(vue): remove legacy js files and layouts" || true

git add tests/Feature/Permission* tests/Feature/Role* tests/Feature/User* 2>/dev/null
git commit -m "test: add permission, role, and user management tests" || true

git add tests/Feature/Security* tests/Feature/*EdgeCase* tests/Feature/PasswordResetTest.php tests/Feature/TrackUserDeviceTest.php 2>/dev/null
git commit -m "test: add security, auth, and edge case tests" || true

git add tests/Feature/ tests/Unit/ tests/Traits/ 2>/dev/null
git commit -m "test: add scheduling, localization, and core unit tests" || true

git add .
git commit -m "chore: final cleanup and scripts addition" || true

echo "Commits created successfully."

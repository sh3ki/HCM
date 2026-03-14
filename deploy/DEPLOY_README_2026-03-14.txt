HCM Update Package - 2026-03-14

ZIP file:
- hcm_update_2026-03-14.zip

Database update script:
- db_update_2026-03-14.sql

Contains these updated files (relative to project root):
- views/login.php
- views/compensation.php
- views/payroll.php
- views/includes/scripts.php

Deployment target:
- /home/hr4.lumino-ph.com/public_html/

Safe deployment steps (File Manager):
1) Backup current files listed above from public_html.
2) Upload hcm_update_2026-03-14.zip into public_html.
3) Extract zip inside public_html.
4) Confirm all 4 files are overwritten in the same relative paths.
5) Run database update:
   - Open phpMyAdmin for hr4_hcm_system database
   - Make sure hr4_hcm_system is selected in the left sidebar
   - Import db_update_2026-03-14.sql
6) Test:
   - Admin login OTP flow
   - Login page remember/forgot hidden
   - Compensation page no delete button
   - Payroll row click opens payslip
   - Dashboard and other cards navigate to relevant pages

Permissions guidance:
- Files: 644
- Directories: 755

Rollback:
- Restore backup files if anything unexpected occurs.

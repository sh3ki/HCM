# Quick Start: Auto-Password & OTP Tracking

## 🚀 Setup (Run Once)

1. Open in browser:
   ```
   http://localhost/HCM/database/run_auto_password_migration.php
   ```

2. Verify success message shows both columns added

3. Done! ✅

## 📋 How It Works

### When Employee is Created:
```
✓ User account created
✓ auto_password_changed = 1 (using auto-generated password)
✓ is_new = 1 (needs OTP verification)
✓ Email sent with credentials
```

### When Employee First Logs In:
```
✓ Login successful
✓ OTP verification modal appears (because is_new = 1)
✓ User enters OTP code
✓ System sets is_new = 0 (permanently verified)
```

### When User Changes Password:
```
✓ User enters new password
✓ Password updated
✓ auto_password_changed = 0 (permanently changed)
✓ User now has personalized password
```

## 🔍 Quick Checks

### See users with auto-passwords:
```sql
SELECT username, email, created_at 
FROM users 
WHERE auto_password_changed = 1;
```

### See unverified users:
```sql
SELECT username, email, created_at 
FROM users 
WHERE is_new = 1;
```

## ✅ Status Meanings

| Column | Value | Meaning |
|--------|-------|---------|
| `auto_password_changed` | 1 | Still using auto-generated password |
| `auto_password_changed` | 0 | Changed to personal password ✓ |
| `is_new` | 1 | Needs OTP verification |
| `is_new` | 0 | OTP verified ✓ |

## 🎯 Key Points

- Both flags start as `1` for new employees
- `is_new` becomes `0` after OTP verification (permanent)
- `auto_password_changed` becomes `0` after password change (permanent)
- Once `0`, they stay `0` forever
- No need to manually manage these - system handles automatically

## 📝 Summary

**UNDERSTOOD ✓**
- `auto_password_changed = 1` on creation → `0` after password change
- `is_new = 1` on creation → `0` after OTP verification
- Both changes are permanent

---
Ready to use! Create an employee and test it.

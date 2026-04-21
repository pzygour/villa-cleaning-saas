# Phase 8 Launch Readiness Checklist

Use this checklist before promoting to production.

## 1) Environment / runtime
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=0`
- [ ] `SESSION_TIMEOUT_SECONDS` set for operations policy (default `3600`)
- [ ] `LOGIN_MAX_ATTEMPTS` reviewed (default `5`)
- [ ] `LOGIN_LOCKOUT_SECONDS` reviewed (default `300`)
- [ ] DB credentials set and reachable
- [ ] Log file path writable (`config/logging.php` target)
- [ ] App served behind HTTPS in production

## 2) Security smoke checks
- [ ] Login works with valid credentials
- [ ] Repeated failed login attempts trigger throttle (`429 too_many_attempts`)
- [ ] Logout invalidates session and redirects to login page
- [ ] Mutating request without CSRF token returns `419 csrf_invalid`
- [ ] Expired session returns `401 session_expired`

## 3) Critical workflow smoke checks
- [ ] Create booking
- [ ] Update/cancel booking
- [ ] Generate cleaning events for a property
- [ ] Run at least one inventory movement/reservation action
- [ ] Create laundry handover
- [ ] Process partial/full laundry return
- [ ] Run requirements totals queries (event/day/property-range)

## 4) Operational checks
- [ ] Confirm auth/security events are written to log file:
  - `login_success`, `login_failed`, `login_throttled`, `logout`, `session_expired`, `csrf_invalid`, `forbidden_access`
- [ ] Verify base-path deployment works (e.g. `/villas/public`) for admin pages and API calls
- [ ] Keep rollback plan ready (DB backup + previous release artifact)

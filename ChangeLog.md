# CHANGELOG WHATSAPP FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.3 - 2026-01-13

### Added
- New `whatsapp_notification_datetime` extrafield for ActionComm to schedule WhatsApp notifications
- Translations for notification datetime field in all supported languages (EN, ES, DE, FR, IT, PT)

### Changed
- WhatsApp reminder cron job now uses `whatsapp_notification_datetime` instead of `datep` for more precise scheduling
- Module version updated to 1.3

### Fixed
- SQL syntax error in `data.sql` - missing semicolon before last INSERT statement
- Tooltip spacing inconsistency in `WHATSAPP_MESSAGE_SENT_ON_TIMETooltip` across all language files

### Technical Improvements
- Enhanced cron job query to check for null notification datetime values
- Improved scheduling logic for WhatsApp reminders

## 1.1 - 2025-11-06

### Added
- Premium glassmorphism design system for floating action button (FAB)
- Advanced CSS animations with cubic-bezier easing functions
- Multi-layer box-shadow effects for depth perception
- Rotating glow effect on main button hover
- Badge pulse animation with gradient backgrounds
- Backdrop-filter with blur effects for modern UI
- Elastic bounce animations for card interactions
- Gradient borders and refined visual hierarchy

### Changed
- Complete redesign of WhatsApp FAB with professional appearance
- Improved hover interactions with smooth transitions
- Enhanced visual feedback on user interactions
- Optimized z-index layering for proper element stacking
- Refined button sizing and spacing for better usability
- Updated badge styling with more prominent appearance
- Improved responsive design for mobile devices

### Fixed
- Z-index issues causing option cards to appear behind main button
- Hover functionality conflicting with JavaScript toggle
- CSS overflow clipping scaled elements on hover
- Visual hierarchy problems in floating card stack
- Transform animations affecting element positioning

### Technical Improvements
- Removed JavaScript toggle in favor of pure CSS :hover
- Optimized animation performance with GPU acceleration
- Enhanced cross-browser compatibility with -webkit- prefixes
- Improved semantic structure of FAB container
- Refined animation timing and delays for smoother UX

## 1.0

Initial version


const style = getComputedStyle(document.body);
window.primaryColor = style.getPropertyValue('--primary');
window.secondaryColor = style.getPropertyValue('--secondary');
window.infoColor = style.getPropertyValue('--info');
window.warningColor = style.getPropertyValue('--warning');
window.dangerColor = style.getPropertyValue('--danger');
window.successColor = style.getPropertyValue('--success');

// Includes
require('./../design_1/app_includes/toast');
require('./../design_1/app_includes/ajax_setup');
require('./main_includes/main')

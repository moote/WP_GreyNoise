/**
 * Show the log table CVE list and hide the 'show' link
 */
function showCveList(){
    document.getElementById('wpg-cve-show-hidden').classList.remove('hidden');
    document.getElementById('wpg-cve-show').classList.add('hidden');
}

/**
 * Hide the log table CVE list and show the 'show' link
 */
function hideCveList(){
    document.getElementById('wpg-cve-show-hidden').classList.add('hidden');
    document.getElementById('wpg-cve-show').classList.remove('hidden');
}

/**
 * Confirm delete for delete actions
 */
function confirmDelete(){
    return confirm('Are you sure?');
}
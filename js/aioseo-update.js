jQuery(document).ready(function($) {
    // Target AIOSO input field
    const aiosInputField = $('.aioseo-input-container .aioseo-input input.medium');

    // Check if the field exists
    if (aiosInputField.length) {
        // Update the AIOSO field with the keyword
        aiosInputField.val(aioseoData.keyword);

        // Log success
        console.log('AIOSO field updated with keyword:', aioseoData.keyword);
    } else {
        console.log('AIOSO input field not found.');
    }
});

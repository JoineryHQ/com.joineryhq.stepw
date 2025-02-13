// fixme: this file is not being used, because apparently back-button will cause
// a full page reload on afform pages, which is what we want.
window.addEventListener("pageshow", function (event) {
  var historyTraversal = event.persisted ||
          (typeof window.performance != "undefined" &&
                  window.performance.navigation.type === 2);
  if (historyTraversal) {
    // Handle page restore.
    console.log('traversal');
//    window.location.reload();
  }
});
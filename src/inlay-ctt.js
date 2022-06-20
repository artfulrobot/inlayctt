import ClickToTweet from './ClickToTweet.svelte';

console.log("Running outer inlay-ctt.js");
(() => {
  console.log("Running inlay-ctt.js");
  if (!window.inlayCTTInit) {
    console.log("Defining inlayCTTInit");
    // This is the first time this *type* of Inlay has been encountered.
    // We need to define anything global here.

    // Create the boot function.
    window.inlayCTTInit = inlay => {

      const appDivNode = document.createElement('div');
      inlay.script.insertAdjacentElement('afterend', appDivNode);
      console.debug('CTT', inlay);

      const app = new ClickToTweet({
        target: appDivNode,
        props: { inlay }
      });
    }
  }

})();


<script>
  // Props
  export let inlay;

  let containerNode;

  let stage = 'postcode';
  let chosenToTakeAction = false;
  let actionStage = 'dunno';
  let postcodeSearchRunning = false;
  let postcode = '';
  let postcodeError = '';
  let tweet = '';
  let tweetLink = '';
  let mpName, mpTwitter, parliamentary_constituency;

  function runPostcodeSearch() {
    const simpler = postcode.toLowerCase().replace(/[^a-z0-9]+/, '');
    if (!simpler.match(/^[a-z0-9]{4,10}$/)) {
      postcodeError = 'Your postcode does not look right.';
      return;
    }
    // Handle the test postcode.
    if (simpler === 'xx11xx') {
      showInfoFor({
        status: 200,
        result: { codes: { ccg_id: 'XX1' }, postcode: 'XX1 1XX' }
      });
    }
    else {
      postcodeSearchRunning = true;
      fetch('https://postcodes.io/postcodes/' + simpler)
        .then(response => response.json())
        .then(data => {
          // Got postcodes IO response, now look up data from Civi.
          showInfoFor(data);
        })
        .catch(e => {
          reportError({error: 'postcodes.io API fail', response: e});
          postcodeError = 'Sorry, there was an error fetching information about your postcode. Please check it is entered correctly.';
          postcodeSearchRunning = false;
        });
    }
  }

  function showInfoFor(postcodesIoData) {
    if (!(postcodesIoData.status === 200 && postcodesIoData.result.codes && postcodesIoData.result.codes.ccg_id)) {
      reportError({error: 'Postcode lookup did not include CCG info', postcodesIoData});
      postcodeError = 'Sorry, we were unable to load information about your postcode.';
      postcodeSearchRunning = false;
      return;
    }

    // Correct postcode formatting.
    postcode = postcodesIoData.result.postcode;
    parliamentary_constituency = postcodesIoData.result.parliamentary_constituency;

    // Prepare request.
    const d = {
      need: 'campaignFromPostcode',
      postcode,
      parliamentary_constituency
    };
    // Send request.
    inlay.request({method: 'post', body: d})
      .then(r => {
        console.log("DATA ", r);
        stage = 'info';
        postcodeSearchRunning = false;
        ( {tweet, mpName, mpTwitter} = r );

        tweetLink = `https://twitter.com/intent/tweet/?text=${encodeURIComponent(tweet)}`;
      });
  }

  function reportError(obj) {
    console.warn(obj);
  }

  function xxxscrollIframeIntoView() {
    // Scroll top of iframe into view again.
    const headerOffset = 100; // Allow 100px for headers
    window.scrollTo({
      top: iframeNode.getBoundingClientRect().top - document.body.getBoundingClientRect().top - headerOffset,
      behavior: 'smooth'
    });

  }


</script>

<style lang="scss">

  .inlayctt-postcode-form {
    text-align:center;
    &>div {
      width: auto;
      margin: 1rem auto;
    }
    &>p {
      width: auto;
      max-width: 50ch;
      margin: 1rem auto;
    }
  }

  .inlayctt-twitter {
    display: inline-block;
    background: #53a6ff;
    color: white;
    border: none;
  }

</style>

<div bind:this={containerNode} class="inlayctt-app">

  {#if stage==='postcode' }
  <form action='#' class="inlayctt-postcode-form" >
    <!-- todo campaign intro -->

    <div class="inlayctt-postcode">
      <label for="inlayctt-postcode">Postcode</label><br/>
      <input id="inlayctt-postcode" type="text" required on:change={runPostcodeSearch} bind:value={postcode} disabled={postcodeSearchRunning} />
      {#if postcodeError }
      <div class="postcode-error" >{postcodeError}</div>
      {/if}
    </div>
    <div class="inlayctt-postcode-submit">
      <button on:click|preventDefault={runPostcodeSearch} disabled={postcodeSearchRunning} >Lookup</button>
    </div>
  </form>

  {:else if stage==='info' }
  <div class="inlayctt-info">
    <p>The MP for <strong>{parliamentary_constituency}</strong> is <strong>{mpName}</strong>.</p>
    {#if !mpTwitter}
      <p>We do not have a Twitter account for this MP, but you can show your support by tweeting the following:</p>
    {/if}

    {#if tweet}
      <blockquote>
        <p>{tweet}</p>
      </blockquote>

      <div style="text-align: center;"><a class="cta inlayctt-twitter" href={tweetLink} target=_blank ><i class="icon twitter-white"></i>Tweet</a></div>
    {/if}

  </div>
  {/if}
</div>

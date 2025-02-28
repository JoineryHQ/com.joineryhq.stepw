/**
 * Enforce on-page behaviors before enabling next-step buttons on this page.
 * 
 * Depends on vimeo sdk (https://developer.vimeo.com/player/sdk/reference)
 */

jQuery(function ($) {
  var iframe = $("figure.is-type-video iframe")[0];
  var player = new Vimeo.Player(iframe);
  var maxTimestampWatched = 0;

  player.on('ended', function () {
    stepwEnforcer.passEnforcement();
  });
  
  player.on("timeupdate", function(data) {
    // 'timeupdate' event fires:
    // - continually as the video progresses (roughly every 250ms)
    // - AND simultaneously with the 'seeked' event.
    //
    // We'll update maxTimestampWatched to the current position if both of these 
    // are true:
    // - new position is less than 1 second ahead of maxTimestampWatched
    // - AND new position is at all ahead of maxTimestampWatched
    // This check is required because of the way 'timeupdate' is fired:
    // - On normal play, it fires every 250ms or so, and in this case we want to update maxTimestampWatched
    //   (new position is ahead of maxTimestampWatched, and only by 250ms or so.)
    // - On 'seeked' event, something interesting happens:
    //    - 'timeupdate' fires with 'seconds'=[new seeked target position]
    //    - 'seeked' event fires, also with 'seconds'=[new seeked target position]
    //    In this case we don't want to update maxTimestampWatched, beccause:
    //    - If the user is seeking FORWARD by more than 1 second (new position
    //      is more than 1 second ahead of maxTimestampWatched), we don't want
    //      them skipping that content.
    //    - If the user is seekng BACKWARD (new timestamp is less than maxTimestampWatched),
    //      we'll still let them seek forward again up to wherever they've legitimately
    //      watched to (which is the meaning of maxTimestampWatched).
    //
    var newTimestamp = data.seconds;
    if ((newTimestamp - maxTimestampWatched) < 1 && newTimestamp > maxTimestampWatched) {
      maxTimestampWatched = data.seconds;
    }
  });

  player.on("seeked", function(data) {
    if (maxTimestampWatched < data.seconds) {
      player.setCurrentTime(maxTimestampWatched);
    }
  });

});








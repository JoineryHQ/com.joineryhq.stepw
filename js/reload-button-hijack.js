/*global CRM, ts */
  console.log('hijack 1');

CRM.$(function($) {
  console.log('hijack');
  $('a.stepwise-button').click(function (e){
    e.preventDefault();
    console.log('this', this);
    window.history.replaceState("", "", this.href);
    history.go(0);
  });
});
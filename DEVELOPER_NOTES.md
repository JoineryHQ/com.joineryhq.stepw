# Developer notes for CiviCRM Stepwise Workflows extension

## FLAG_STEPW_AFFORM_BRITTLE

Functions or code marked with this comment are believed to be most likely to
break as a result of changes beyond our control in future versions of FormBuilder
(afform).

- Functions containing such brittle code should be marked with this comment, _without_
  additional explanation, in the docblock.
- Specific lines of code _within those functions_ should also be marked with this
  comment, _with_ some explanation of the foreseen potential causes of breakage,
  e.g. "afform could ... which would be bad becase ...; in that case we would 
  need to ..." (and if possible, a link to the afform code, on github.com at a
  certain civicrm version, for which our own code is relevant, e.g. "Reference 
  https://github.com/civicrm/civicrm-core/blob/5.81.0/ext/afform/core/Civi/Api4/Action/Afform/Submit.php#L41").

## Params via 'referer' vs 'request':

In certain ajax-driven workflows, this extension looks for relevant parameters
via HTTP referer, rather than the current HTTP request. This was deemed expedient
as no other means was easily found for adding our own parameters into afform requests
(such as form submission and prefill). Therefore, special care is required in
assuring that only valid query parameters are used in URLs that will load pages
in such contexts; where invalid parameters are given, the user should be redirected
away from the afform page -- typically to via a thrown CRM_Stepw_Exception.
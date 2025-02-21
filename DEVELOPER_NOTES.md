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

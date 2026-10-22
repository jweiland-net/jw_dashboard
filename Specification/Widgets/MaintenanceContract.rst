.. include:: /Includes.rst.txt

.. _widget-maintenance-contract:

====================
Maintenance Contract
====================

:Setting: :confval:`enableWidgetMaintenanceContract`
:Size: medium
:Refresh button: no

Lists all contract records which are not cancelled, the most urgent first.
Each contract shows client, contract type, assigned contact persons and a
status badge:

- **Active** — end date is outside the warning period.
- **Renewal due** — end date is within the warning period of the record
  (fallback: :confval:`contractRenewalWarningDays`).
- **Expired** — end date is in the past.
- **Unlimited** — no end date.

A meter below each contract shows the elapsed share of the contract term, colored
by its status.

Without any contract a sign-up prompt is shown (:confval:`showContractSignupPrompt`,
:confval:`contractSignupUrl`). Admins get a :guilabel:`Manage contracts` link
to the records on root level.

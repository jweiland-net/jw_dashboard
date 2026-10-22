.. include:: /Includes.rst.txt

.. _widget-content-activity:

================
Content Activity
================

:Setting: :confval:`enableWidgetContentActivity`
:Size: large
:Refresh button: yes

Column chart of backend record changes per calendar week, based on the record
history (``sys_history``). Only the peak week and the current week (amber, still
in progress) are labelled; every bar shows its value on hover or keyboard focus,
and :guilabel:`Show data as table` lists all values.

The KPI tiles above the chart show the number of changes, created and updated
records and active editors of the period.

Settings: :confval:`activityWeeks`.

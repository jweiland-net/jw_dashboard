.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

Requirements
============

- TYPO3 14.0.0 or later
- PHP 8.2 or later
- Composer-based TYPO3 installation

Composer Installation
=====================

.. code-block:: bash

   composer require jweiland/jw-dashboard

Activate the extension in the Extension Manager or via CLI:

.. code-block:: bash

   vendor/bin/typo3 extension:activate jw_dashboard

Database Update
===============

Run the Database Analyser in the Install Tool or via CLI:

.. code-block:: bash

   vendor/bin/typo3 database:updateschema

This creates the following tables:

- ``tx_jwdashboard_contact_person``
- ``tx_jwdashboard_contract``
- ``tx_jwdashboard_contract_contactperson_mm``

Maintenance Contracts and Contact Persons
=========================================

Contract and contact person records are global records. Create them as admin
on the root level (page id 0) with the :guilabel:`Content > Records` module,
or use the :guilabel:`Manage contracts` link in the Maintenance Contract
widget. Records stored on any other page or folder are shown as well.

Mark one contact person as :guilabel:`Primary Contact` to show it as personal
contact in the Editor Guide widget.

Sample Data
===========

To preview the Maintenance Contract and Editor Guide widgets, create demo
records (four contracts covering every renewal state and three fictional
contact persons on root level):

.. code-block:: bash

   vendor/bin/typo3 jwdashboard:sample-data

Running the command again replaces the sample records. Remove them with
``vendor/bin/typo3 jwdashboard:sample-data --remove``; other records are never
touched.

Front-End Assets
================

The extension ships plain CSS (``Resources/Public/Css/dashboard.css``) and a
native ES module (``Resources/Public/JavaScript/dashboard.js``), which is
loaded via the TYPO3 import map. No build step is required.

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Notification from '@typo3/backend/notification.js';
import RegularEvent from '@typo3/core/event/regular-event.js';
import { countUp } from '@jweiland/jw-dashboard/count-up.js';

/**
 * - Loads deferred widgets (slow data sources) after the page has been rendered
 * - Refreshes a single widget card: the server discards cached data and returns the re-rendered body
 */
class Dashboard {
  constructor() {
    countUp(document);

    new RegularEvent('click', (event, button) => {
      event.preventDefault();
      this.refresh(button);
    }).delegateTo(document, '[data-widget-refresh]');

    document.querySelectorAll('[data-widget-deferred]').forEach((widget) => this.loadDeferred(widget));
  }

  async loadDeferred(widget) {
    const body = widget.querySelector('[data-widget-body]');
    try {
      body.innerHTML = await this.fetchBody(widget.dataset.widgetId, false);
      widget.removeAttribute('data-widget-deferred');
      this.revealBody(body);
    } catch {
      body.innerHTML = '';
      body.textContent = TYPO3.lang['dashboard.widget.notAvailable'] ?? 'Widget data is currently unavailable.';
    } finally {
      body.setAttribute('aria-busy', 'false');
    }
  }

  async refresh(button) {
    const widget = button.closest('[data-widget-id]');
    const body = widget?.querySelector('[data-widget-body]');
    if (!body || button.disabled) {
      return;
    }

    this.setLoading(button, body, true);
    try {
      body.innerHTML = await this.fetchBody(button.dataset.widgetRefresh, true);
      this.revealBody(body);
    } catch {
      Notification.error(TYPO3.lang['dashboard.widget.refreshFailed'] ?? 'The widget could not be refreshed.');
    } finally {
      this.setLoading(button, body, false);
    }
  }

  async fetchBody(widgetIdentifier, refresh) {
    const queryArguments = { widget: widgetIdentifier };
    if (refresh) {
      queryArguments.refresh = 1;
    }

    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.jw_dashboard_widget_refresh)
      .withQueryArguments(queryArguments)
      .get();
    const data = await response.resolve();

    return data.html;
  }

  /**
   * Replays the entrance animation of the new content and starts the number animations
   */
  revealBody(body) {
    body.classList.remove('is-updated');
    // Force reflow, so the animation restarts on subsequent refreshes
    void body.offsetWidth;
    body.classList.add('is-updated');
    countUp(body);
  }

  setLoading(button, body, isLoading) {
    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);
    body.setAttribute('aria-busy', isLoading ? 'true' : 'false');
  }
}

export default new Dashboard();

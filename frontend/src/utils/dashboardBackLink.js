export function dashboardBackLink(pathname, homePath) {
  const bookingRoute = pathname.match(/^\/(client|provider|admin)\/bookings(?:\/([^/]+))?(?:\/receipt)?$/);

  if (bookingRoute) {
    if (!bookingRoute[2]) return null;
    return {
      to: pathname.endsWith('/receipt') ? `/${bookingRoute[1]}/bookings/${bookingRoute[2]}` : `/${bookingRoute[1]}/bookings`,
      label: 'Back to booking',
    };
  }

  const parentRoutes = [
    [/^\/provider\/services\/(?:new|[^/]+\/edit)$/, '/provider/services', 'Back to services'],
    [/^\/(client|provider)\/messages\/[^/]+$/, (match) => `/${match[1]}/messages`, 'Back to messages'],
    [/^\/client\/providers\/map$/, '/client/providers', 'Back to providers'],
    [/^\/client\/providers\/[^/]+$/, '/client/providers', 'Back to providers'],
    [/^\/client\/services\/[^/]+$/, '/client/search', 'Back to search'],
    [/^\/admin\/providers\/[^/]+$/, '/admin/providers', 'Back to providers'],
    [/^\/admin\/violations\/[^/]+$/, '/admin/violations', 'Back to chat violations'],
  ];

  for (const [pattern, destination, label] of parentRoutes) {
    const match = pathname.match(pattern);
    if (match) return { to: typeof destination === 'function' ? destination(match) : destination, label };
  }

  return homePath && pathname !== homePath
    ? { to: homePath, label: 'Back to Dashboard' }
    : null;
}

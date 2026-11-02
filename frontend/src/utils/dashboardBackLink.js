export function dashboardBackLink(pathname, homePath) {
  const bookingRoute = pathname.match(/^\/(client|provider|admin)\/bookings(?:\/([^/]+))?(?:\/receipt)?$/);

  if (bookingRoute) {
    if (!bookingRoute[2]) return null;
    return {
      to: pathname.endsWith('/receipt') ? `/${bookingRoute[1]}/bookings/${bookingRoute[2]}` : `/${bookingRoute[1]}/bookings`,
      label: 'Back to booking',
    };
  }

  return homePath && pathname !== homePath
    ? { to: homePath, label: 'Back to Dashboard' }
    : null;
}

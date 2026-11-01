export const BOOKING_PIN_RADIUS_KM = 2;

export function isWithinBookingPinRange(latitude, longitude, center) {
  if (center?.latitude == null || center?.longitude == null) return false;
  const values = [latitude, longitude, center.latitude, center.longitude].map(Number);
  if (values.some((value) => !Number.isFinite(value))) return false;

  const [pinLat, pinLng, centerLat, centerLng] = values.map((value) => value * Math.PI / 180);
  const haversine = Math.sin((pinLat - centerLat) / 2) ** 2
    + Math.cos(centerLat) * Math.cos(pinLat) * Math.sin((pinLng - centerLng) / 2) ** 2;
  const distanceKm = 6371 * 2 * Math.asin(Math.sqrt(Math.min(1, haversine)));

  return distanceKm <= BOOKING_PIN_RADIUS_KM;
}

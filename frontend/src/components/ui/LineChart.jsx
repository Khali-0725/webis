/**
 * Minimal SVG line chart for admin analytics - no charting library, matching
 * this codebase's existing "plain CSS bar" approach to the rating
 * distribution (see ProviderProfilePage) rather than adding a dependency for
 * a handful of charts.
 */
const WIDTH = 600;
const HEIGHT = 180;
const PAD_X = 8;
const PAD_Y = 12;

function formatShortDate(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

export function LineChart({ points, valueKey = 'total', labelKey = 'date', color = '#0f766e', formatValue }) {
  if (!points || points.length === 0) {
    return <p className="text-sm text-ink-muted">Not enough data yet.</p>;
  }

  const values = points.map((p) => Number(p[valueKey]) || 0);
  const max = Math.max(...values, 1);
  const min = 0;

  const stepX = points.length > 1 ? (WIDTH - PAD_X * 2) / (points.length - 1) : 0;

  const coords = values.map((v, i) => {
    const x = PAD_X + i * stepX;
    const y = PAD_Y + (1 - (v - min) / (max - min || 1)) * (HEIGHT - PAD_Y * 2);
    return [x, y];
  });

  const linePath = coords.map(([x, y], i) => `${i === 0 ? 'M' : 'L'}${x},${y}`).join(' ');
  const areaPath = `${linePath} L${coords[coords.length - 1][0]},${HEIGHT - PAD_Y} L${coords[0][0]},${HEIGHT - PAD_Y} Z`;

  const gradientId = `line-chart-gradient-${valueKey}`;
  const display = formatValue ?? ((v) => v);

  return (
    <div>
      <svg viewBox={`0 0 ${WIDTH} ${HEIGHT}`} className="w-full" preserveAspectRatio="none" role="img">
        <defs>
          <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={color} stopOpacity="0.25" />
            <stop offset="100%" stopColor={color} stopOpacity="0" />
          </linearGradient>
        </defs>
        <line x1={PAD_X} y1={HEIGHT - PAD_Y} x2={WIDTH - PAD_X} y2={HEIGHT - PAD_Y} stroke="#e2e8f0" strokeWidth="1" />
        <path d={areaPath} fill={`url(#${gradientId})`} stroke="none" />
        <path d={linePath} fill="none" stroke={color} strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
        {coords.map(([x, y], i) => (
          <circle key={i} cx={x} cy={y} r="2.5" fill={color} />
        ))}
      </svg>
      <div className="mt-1 flex items-center justify-between text-xs text-ink-muted">
        <span>{formatShortDate(points[0][labelKey])}</span>
        {points.length > 2 && (
          <span>{formatShortDate(points[Math.floor((points.length - 1) / 2)][labelKey])}</span>
        )}
        <span>{formatShortDate(points[points.length - 1][labelKey])}</span>
      </div>
      <p className="mt-1 text-right text-xs font-medium text-ink">Peak: {display(max)}</p>
    </div>
  );
}

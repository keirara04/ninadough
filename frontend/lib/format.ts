export function formatSen(sen: number): string {
  return `RM${(sen / 100).toFixed(2)}`;
}

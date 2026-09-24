/**
 * Formats a number to Philippine Peso currency string (₱XX.XX)
 */
export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    currencyDisplay: 'symbol',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount)
}

/**
 * Calculates discount percentage saved amount
 */
export const calculateSavings = (price: number, originalPrice?: number): number => {
  if (!originalPrice || originalPrice <= price) return 0
  return originalPrice - price
}

/**
 * Formats age group label nicely
 */
export const formatAgeGroup = (ageGroup: string): string => {
  switch (ageGroup) {
    case '0-2':
      return '0 - 2 Years'
    case '3-5':
      return '3 - 5 Years'
    case '6-8':
      return '6 - 8 Years'
    case '9-12':
      return '9 - 12 Years'
    case '12+':
      return '12+ Years'
    default:
      return 'All Ages'
  }
}

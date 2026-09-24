import * as yup from 'yup'

export const toyFilterSchema = yup.object({
  searchQuery: yup.string().default(''),
  category: yup
    .string()
    .oneOf([
      'all',
      'tcg',
      'anime-figures',
      'anime-merchandise',
    ])
    .default('all'),
  tcgSeries: yup
    .string()
    .oneOf(['all', 'pokemon', 'one-piece', 'hololive', 'duel-masters', 'weiss-schwarz'])
    .default('all'),
  ageGroup: yup
    .string()
    .oneOf(['all', '0-2', '3-5', '6-8', '9-12', '12+'])
    .default('all'),
  minPrice: yup.number().min(0, 'Min price cannot be negative').default(0),
  maxPrice: yup.number().max(500, 'Max price cannot exceed 500').default(100),
  minRating: yup.number().min(0).max(5).default(0),
  sortBy: yup
    .string()
    .oneOf(['featured', 'price-asc', 'price-desc', 'rating', 'newest'])
    .default('featured'),
  onlyInStock: yup.boolean().default(false),
  onlyDiscounted: yup.boolean().default(false),
})

export type ToyFilterInput = yup.InferType<typeof toyFilterSchema>

export const toyReviewSchema = yup.object({
  author: yup.string().trim().required('Please provide your name').min(2, 'Name too short'),
  rating: yup.number().required('Please select a star rating').min(1).max(5),
  comment: yup
    .string()
    .trim()
    .required('Please leave your thoughts')
    .min(10, 'Review should be at least 10 characters'),
})

export type ToyReviewInput = yup.InferType<typeof toyReviewSchema>

import { describe, it, expect } from 'vitest'
import { toyFilterSchema, toyReviewSchema } from './toy-product.schema'

describe('toy-product.schema', () => {
  it('validates default toy filter parameters successfully', async () => {
    const defaultParams = {}
    const parsed = await toyFilterSchema.validate(defaultParams)
    expect(parsed.category).toBe('all')
    expect(parsed.ageGroup).toBe('all')
    expect(parsed.minPrice).toBe(0)
    expect(parsed.maxPrice).toBe(100)
    expect(parsed.sortBy).toBe('featured')
  })

  it('rejects negative minimum price', async () => {
    await expect(
      toyFilterSchema.validate({ minPrice: -10 }),
    ).rejects.toThrow('Min price cannot be negative')
  })

  it('validates valid user toy review', async () => {
    const validReview = {
      author: 'Sarah Jenkins',
      rating: 5,
      comment: 'My 5-year-old loved building this space shuttle!',
    }
    const parsed = await toyReviewSchema.validate(validReview)
    expect(parsed.author).toBe('Sarah Jenkins')
    expect(parsed.rating).toBe(5)
  })

  it('rejects review with short comment', async () => {
    const invalidReview = {
      author: 'John',
      rating: 4,
      comment: 'Good',
    }
    await expect(toyReviewSchema.validate(invalidReview)).rejects.toThrow(
      'Review should be at least 10 characters',
    )
  })
})

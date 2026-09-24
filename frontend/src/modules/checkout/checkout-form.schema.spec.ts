import { describe, it, expect } from 'vitest'
import { checkoutFormSchema } from './checkout-form.schema'

describe('checkout-form.schema', () => {
  it('validates a complete valid checkout payload with credit card', async () => {
    const validData = {
      firstName: 'Emily',
      lastName: 'Watson',
      email: 'emily.watson@example.com',
      phone: '+1 555-0199',
      streetAddress: '742 Evergreen Terrace',
      city: 'Springfield',
      postalCode: '97477',
      deliveryOption: 'gift-wrapped',
      giftMessage: 'Happy 6th Birthday Leo!',
      paymentMethod: 'card',
      cardNumber: '4532 1123 4567 8901',
      cardExpiry: '12/28',
      cardCvv: '789',
    }

    const parsed = await checkoutFormSchema.validate(validData)
    expect(parsed.firstName).toBe('Emily')
    expect(parsed.deliveryOption).toBe('gift-wrapped')
  })

  it('validates Cash on Delivery without requiring card fields', async () => {
    const validCodData = {
      firstName: 'Michael',
      lastName: 'Scott',
      email: 'michael@dunder.com',
      phone: '555-123-4567',
      streetAddress: '1725 Slough Ave',
      city: 'Scranton',
      postalCode: '18503',
      deliveryOption: 'standard',
      paymentMethod: 'cod',
    }

    const parsed = await checkoutFormSchema.validate(validCodData)
    expect(parsed.paymentMethod).toBe('cod')
    expect(parsed.cardNumber).toBeUndefined()
  })

  it('rejects invalid email address', async () => {
    const invalidEmailData = {
      firstName: 'Test',
      lastName: 'User',
      email: 'not-an-email',
      phone: '1234567',
      streetAddress: '123 Toy St',
      city: 'Playtown',
      postalCode: '12345',
      paymentMethod: 'cod',
    }

    await expect(checkoutFormSchema.validate(invalidEmailData)).rejects.toThrow(
      'Please enter a valid email address',
    )
  })
})

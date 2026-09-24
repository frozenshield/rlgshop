import * as yup from 'yup'

export const cartItemSchema = yup.object({
  toyId: yup.string().required('Toy ID is required'),
  quantity: yup
    .number()
    .required('Quantity is required')
    .integer('Must be whole number')
    .min(1, 'Quantity must be at least 1')
    .max(99, 'Quantity cannot exceed 99'),
})

export type CartItemValidationSchema = yup.InferType<typeof cartItemSchema>

export const promoCodeSchema = yup.object({
  code: yup.string().trim().uppercase().required('Please enter a promo code'),
})

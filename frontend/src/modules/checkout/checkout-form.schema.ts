import * as yup from 'yup'

export const checkoutFormSchema = yup.object({
  firstName: yup
    .string()
    .trim()
    .required('First name is required')
    .min(2, 'Must be at least 2 characters'),
  lastName: yup
    .string()
    .trim()
    .required('Last name is required')
    .min(2, 'Must be at least 2 characters'),
  email: yup
    .string()
    .trim()
    .required('Email address is required')
    .email('Please enter a valid email address'),
  phone: yup
    .string()
    .trim()
    .required('Phone number is required')
    .min(7, 'Please enter a valid phone number'),
  streetAddress: yup
    .string()
    .trim()
    .required('Delivery street address is required')
    .min(5, 'Please provide full address'),
  city: yup.string().trim().required('City is required'),
  postalCode: yup.string().trim().required('Postal code is required'),
  notes: yup.string().trim().default(''),
  deliveryOption: yup
    .string()
    .oneOf(['standard', 'express', 'gift-wrapped'])
    .required('Please select a delivery option')
    .default('standard'),
  giftMessage: yup.string().trim().default(''),
  paymentMethod: yup
    .string()
    .oneOf(['card', 'wallet', 'cod'])
    .required('Please choose a payment method')
    .default('card'),
  cardNumber: yup.string().when('paymentMethod', {
    is: 'card',
    then: (schema) =>
      schema
        .required('Card number is required')
        .matches(/^[0-9\s]{15,19}$/, 'Card number must be 16 digits'),
    otherwise: (schema) => schema.notRequired(),
  }),
  cardExpiry: yup.string().when('paymentMethod', {
    is: 'card',
    then: (schema) =>
      schema
        .required('Expiry date is required')
        .matches(/^(0[1-9]|1[0-2])\/?([0-9]{2})$/, 'Format MM/YY'),
    otherwise: (schema) => schema.notRequired(),
  }),
  cardCvv: yup.string().when('paymentMethod', {
    is: 'card',
    then: (schema) =>
      schema
        .required('CVV is required')
        .matches(/^[0-9]{3,4}$/, 'Must be 3 or 4 digits'),
    otherwise: (schema) => schema.notRequired(),
  }),
})

export type CheckoutFormValidationSchema = yup.InferType<typeof checkoutFormSchema>

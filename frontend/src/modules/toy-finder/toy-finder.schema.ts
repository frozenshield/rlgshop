import * as yup from 'yup'

export const giftAdvisorSchema = yup.object({
  ageGroup: yup.string().oneOf(['0-2', '3-5', '6-8', '9-12', '12+']).required(),
  interest: yup
    .string()
    .oneOf(['building', 'stem', 'figures', 'plush', 'board-games', 'vehicles', 'arts-crafts'])
    .required(),
  budget: yup.number().min(15).max(100).required(),
})

export type GiftAdvisorInput = yup.InferType<typeof giftAdvisorSchema>

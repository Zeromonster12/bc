export const buildAdminUsersParams = (
  page: number,
  search: string,
  role: string,
): { page: number; search: string; role: string } => ({
  page,
  search,
  role,
})

export const filterOutById = <T extends { id: number }>(list: T[], id: number): T[] => {
  return list.filter((item) => item.id !== id)
}

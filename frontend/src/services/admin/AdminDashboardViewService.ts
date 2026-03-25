export const buildAdminUsersParams = (
  page: number,
  search: string,
  role: string,
  companyStatus: string,
): { page: number; search: string; role: string; company_status: string } => ({
  page,
  search,
  role,
  company_status: companyStatus,
})

export const filterOutById = <T extends { id: number }>(list: T[], id: number): T[] => {
  return list.filter((item) => item.id !== id)
}

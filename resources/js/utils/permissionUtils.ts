export const groupPermissionsByModule = (permissions: any[]) => {
    if (!permissions) return {};
    const groups: Record<string, any[]> = {};
    permissions.forEach((p: any) => {
        const module = p.module || "Other";
        if (!groups[module]) groups[module] = [];
        groups[module].push(p);
    });
    return groups;
};

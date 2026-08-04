export interface TableColumn {
    key: string;
    label: string;
    align?: "left" | "center" | "right";
    width?: string;
    class?: string;
    sortable?: boolean;
}

export type TableContext =
    | "users"
    | "roles"
    | "permissions"
    | "libraries"
    | "categories"
    | "campuses"
    | "colleges"
    | "schools"
    | "books"
    | "departments"
    | "courses"
    | "course_outlines"
    | "lecture_notes"
    | "academic_years"
    | "video_lectures"
    | "reference_books"
    | "worksheets"
    | "assignments"
    | "shelves"
    | "circulation_policies"
    | "borrows"
    | "fines"
    | "spot_readings"
    | "wishlists"
    | "attendance"
    | "book_copies"
    | "default";

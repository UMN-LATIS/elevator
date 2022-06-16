export default function smoothingSpline(data: any, { lambda }?: {
    lambda?: number;
}): {
    fn: (x: any) => any;
    points: {
        x: number;
        y: any;
    }[];
};

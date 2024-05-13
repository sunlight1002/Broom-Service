import Sidebar from "../../Admin/Layouts/Sidebar";
import AvailabilityForm from "./AvailabilityForm";

export default function Availibility() {
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">Availability</h1>
                <AvailabilityForm />
            </div>
        </div>
    );
}

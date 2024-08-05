import Sidebar from "../../Admin/Layouts/Sidebar";
import AvailabilityForm from "./AvailabilityForm";
import { useTranslation } from "react-i18next";

export default function Availibility() {
    const { t } = useTranslation();
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">{t("admin.availability")}</h1>
                <AvailabilityForm />
            </div>
        </div>
    );
}

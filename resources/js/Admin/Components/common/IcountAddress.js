import { memo, useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { useAlert } from "react-alert";
// import "bootstrap/dist/css/bootstrap.min.css";

export const IcountAddress = memo(({ clientId }) => {
    const { t } = useTranslation();
    const [propertyAddress, setPropertyAddress] = useState([]);
    const [address, setAddress] = useState({
        icount_client_id: "",
        street: "",
        city: "",
        // country: "",
        zipcode: "",
    });
    const [error, setError] = useState("");

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getPropertyAddress = () => {
        axios
            .get(`/api/admin/clients/${parseInt(clientId)}/icount-address`, {
                headers,
            }).then((res) => {
                setAddress({
                    icount_client_id: res.data.client_id || "",
                    street: res.data.bus_street || "",
                    city: res.data.bus_city || "",
                    // country: lastAddress.country || "",
                    zipcode: res.data.bus_zip || "",
                });
            })
            .catch((error) => {
                // alert.error(error.response.data.message);
                setError(error.response.data.message);
                console.error("Error fetching property address:", error);
            });
    };

    useEffect(() => {
        if (clientId) getPropertyAddress();
    }, [clientId]);

    const handleUpdateIcountAddress = () => {
        axios
            .put(`/api/admin/clients/${clientId}/icount-address`, address, {
                headers,
            })
            .then((res) => {
                alert.success(res.data.message);
            })
            .catch((error) => {
                alert.error(error.response.data.message);
                console.error("Error updating property address:", error);
            });
    };

    return (
        <>
            {
                error ? (
                    <div className="alert alert-danger">⚠️ {error}</div>
                ) : (
                    <div className="border rounded p-4 mt-2 bg-light">
                        <div className="row mb-3">
                            <div className="col-md-6">
                                <label className="form-label">{t("Street")}</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={address.street}
                                    onChange={(e) =>
                                        setAddress({ ...address, street: e.target.value })
                                    }
                                    placeholder={t("Enter street")}
                                />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">{t("City")}</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={address.city}
                                    onChange={(e) =>
                                        setAddress({ ...address, city: e.target.value })
                                    }
                                    placeholder={t("Enter city")}
                                />
                            </div>
                        </div>

                        <div className="row mb-3">
                            {/* <div className="col-md-6">
                    <label className="form-label">{t("Country")}</label>
                    <input
                        type="text"
                        className="form-control"
                        value={address.country}
                        onChange={(e) =>
                            setAddress({ ...address, country: e.target.value })
                        }
                        placeholder={t("Enter country")}
                    />
                </div> */}
                            <div className="col-md-6">
                                <label className="form-label">{t("Zip Code")}</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={address.zipcode}
                                    onChange={(e) =>
                                        setAddress({ ...address, zipcode: e.target.value })
                                    }
                                    placeholder={t("Enter zip code")}
                                />
                            </div>
                        </div>

                        <button
                            type="button"
                            className="btn navyblue"
                            onClick={handleUpdateIcountAddress}
                        >
                            {t("Update")}
                        </button>
                    </div>
                )
            }
        </>
    );
});

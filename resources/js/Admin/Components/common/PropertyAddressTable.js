import { memo, useEffect, useState } from "react";
import { Tooltip } from "react-tooltip";
import { useTranslation } from "react-i18next";

import PropertyAddress from "../Leads/PropertyAddress";

const PropertyAddressTable = memo(function PropertyAddressTable({ clientId }) {
    const [address, setAddress] = useState([]);
    const [errors, setErrors] = useState({});
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getPropertyAddress = () => {
        axios
            .get(`/api/admin/clients/${parseInt(clientId)}/edit`, {
                headers,
            })
            .then((res) => {

                const { client } = res.data;
                if (
                    client.property_addresses &&
                    client.property_addresses.length > 0
                ) {
                    setAddress(res.data.client.property_addresses);
                }
            });
    };

    useEffect(() => {
        getPropertyAddress();
    }, [clientId]);

    return (
        <div>
            <div className="property-container">
                <PropertyAddress
                    // heading={t(
                    //     "admin.leads.AddLead.propertyAddress"
                    // )}
                    errors={errors || {}}
                    setErrors={setErrors || {}}
                    addresses={address}
                    setAddresses={setAddress}
                    newClient={false}
                />
            </div>
            <Tooltip id="address-tooltip" />
        </div>
    );
});

export default PropertyAddressTable;

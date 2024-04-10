import { useTranslation } from "react-i18next";
import CheckBox from "./inputElements/CheckBox";
import DateField from "./inputElements/DateField";
import SelectElement from "./inputElements/SelectElement";
import TextField from "./inputElements/TextField";
import { cityOption } from "./cityCountry";

export default function TaxExemption({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) {
    const { t } = useTranslation();
    return (
        <div>
            <h2>
                H. I request tax exemption or tax credit due to the following
                reasons
            </h2>
            {/* 1 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.isIsraelResident"}
                    label={t("form101.exm1")}
                    disabled
                    checked={values.employeeIsraeliResident === "Yes"}
                    onChange={handleChange}
                    onBlur={handleBlur}
                />
            </div>
            {/* 2 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.disabled"}
                    label={t("form101.exm2")}
                    checked={values.TaxExemption.disabled}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.disabled &&
                        errors.TaxExemption.disabled
                            ? errors.TaxExemption.disabled
                            : ""
                    }
                />
                {values.TaxExemption.disabled && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="employeepassportCopy"
                                className="pt-2"
                            >
                                Certificate from the Ministry of Defence/the
                                Treasury/assessing officer/Certification of
                                Blindness issued after 1/1/94.
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.disabledCertificate"
                                id="employeepassportCopy"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.disabledCertificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.disabledCertificate &&
                                errors.TaxExemption.disabledCertificate && (
                                    <p className="text-danger">
                                        {
                                            errors.TaxExemption
                                                .disabledCertificate
                                        }
                                    </p>
                                )}
                            <div className="pt-2">
                                <CheckBox
                                    name={"TaxExemption.disabledCompensation"}
                                    label={
                                        "2B. In addition, I receive a monthly compensation in accordance with Disabled Law (compensation and rehabilitation) the or the Compensation for Victims of Hostile Acts Law"
                                    }
                                    checked={
                                        values.TaxExemption.disabledCompensation
                                    }
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .disabledCompensation &&
                                        errors.TaxExemption.disabledCompensation
                                            ? errors.TaxExemption
                                                  .disabledCompensation
                                            : ""
                                    }
                                />
                            </div>
                            {values.TaxExemption &&
                                values.TaxExemption.disabledCompensation && (
                                    <div className="row">
                                        <label htmlFor="disabledCompensationCertificate col-12">
                                            Certificate for receiving the
                                            monthly compensation
                                        </label>
                                        <input
                                            type="file"
                                            className="col-12"
                                            name="TaxExemption.disabledCompensationCertificate"
                                            id="disabledCompensationCertificate"
                                            onChange={(e) =>
                                                setFieldValue(
                                                    "TaxExemption.disabledCompensationCertificate",
                                                    e.target.files[0]
                                                )
                                            }
                                            onBlur={handleBlur}
                                        />
                                        {touched.TaxExemption &&
                                            errors.TaxExemption &&
                                            touched.TaxExemption
                                                .disabledCompensationCertificate &&
                                            errors.TaxExemption
                                                .disabledCompensationCertificate && (
                                                <p className="text-danger">
                                                    {
                                                        errors.TaxExemption
                                                            .disabledCompensationCertificate
                                                    }
                                                </p>
                                            )}
                                    </div>
                                )}
                        </div>
                    </>
                )}
            </div>
            {/* 3 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm3"}
                    label={t("form101.exm3")}
                    checked={values.TaxExemption.exm3}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm3 &&
                        errors.TaxExemption.exm3
                            ? errors.TaxExemption.exm3
                            : ""
                    }
                />
                {values.TaxExemption && values.TaxExemption.exm3 && (
                    <div className="row">
                        <div className="col-4">
                            <DateField
                                name="TaxExemption.exm3Date"
                                label="From date"
                                value={values.TaxExemption.exm3Date}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm3Date &&
                                    errors.TaxExemption.exm3Date
                                        ? errors.TaxExemption.exm3Date
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-4">
                            <SelectElement
                                name={"TaxExemption.exm3Locality"}
                                label={"Locality"}
                                value={values.TaxExemption.exm3Locality}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm3Locality &&
                                    errors.TaxExemption.exm3Locality
                                        ? errors.TaxExemption.exm3Locality
                                        : ""
                                }
                                options={cityOption}
                                required={true}
                            />
                        </div>
                        <div className="col-12">
                            <label htmlFor="employeepassportCopy">
                                Locality Certificate from the locality on Form
                                1312-Aleph
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.exm3Certificate"
                                id="exm3Certificate"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.exm3Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.exm3Certificate &&
                                errors.TaxExemption.exm3Certificate && (
                                    <p className="text-danger">
                                        {errors.TaxExemption.exm3Certificate}
                                    </p>
                                )}
                        </div>
                    </div>
                )}
            </div>
            {/* 4 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm4"}
                    label={t("form101.exm4")}
                    checked={values.TaxExemption.exm4}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm4 &&
                        errors.TaxExemption.exm4
                            ? errors.TaxExemption.exm4
                            : ""
                    }
                />
                {values.TaxExemption && values.TaxExemption.exm4 && (
                    <div className="row">
                        {" "}
                        <div className="col-6">
                            <DateField
                                name="exm4FromDate"
                                label="From date"
                                value={values.TaxExemption.exm4FromDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm4FromDate &&
                                    errors.TaxExemption.exm4FromDate
                                        ? errors.TaxExemption.exm4FromDate
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-6">
                            <label htmlFor="exm4ImmigrationCertificate">
                                New immigrant certificate *
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.exm4ImmigrationCertificate"
                                id="exm4ImmigrationCertificate"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.exm4ImmigrationCertificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption
                                    .exm4ImmigrationCertificate &&
                                errors.TaxExemption
                                    .exm4ImmigrationCertificate && (
                                    <p className="text-danger">
                                        {
                                            errors.TaxExemption
                                                .exm4ImmigrationCertificate
                                        }
                                    </p>
                                )}
                        </div>
                        <div className="col-12">
                            <DateField
                                name="exm4NoIncomeDate"
                                label="I have had no income in Israel from the beginning of the tax year until the date"
                                value={values.TaxExemption.exm4NoIncomeDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm4NoIncomeDate &&
                                    errors.TaxExemption.exm4NoIncomeDate
                                        ? errors.TaxExemption.exm4NoIncomeDate
                                        : ""
                                }
                                required
                            />
                            <p>
                                Someone whose period of entitlement is not
                                continuous because of mandatory IDF service,
                                higher studies or a period of time abroad - will
                                refer to the assessing officer.
                            </p>
                        </div>
                    </div>
                )}
            </div>
            {/* 5 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm5"}
                    label={t("form101.exm5")}
                    checked={values.TaxExemption.exm5}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={values.employeeMaritalStatus !== "Married"}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm5 &&
                        errors.TaxExemption.exm5
                            ? errors.TaxExemption.exm5
                            : ""
                    }
                />
                {values.employeeMaritalStatus !== "Married" && (
                    <p className="text-danger">
                        Cannot check because you did not indicate in section B
                        that you're married.
                    </p>
                )}
                {values.TaxExemption && values.TaxExemption.exm5 && (
                    <div>
                        <div className="d-flex gap-5">
                            <div>
                                <label htmlFor="exm5disabledCirtificate">
                                    Disabled or blind certificate for the
                                    employee or the spouse.
                                </label>
                                <br />
                                <input
                                    type="file"
                                    name="TaxExemption.exm5disabledCirtificate"
                                    id="exm5disabledCirtificate"
                                    onChange={(e) =>
                                        setFieldValue(
                                            "TaxExemption.exm5disabledCirtificate",
                                            e.target.files[0]
                                        )
                                    }
                                    onBlur={handleBlur}
                                />
                                {touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption
                                        .exm5disabledCirtificate &&
                                    errors.TaxExemption
                                        .exm5disabledCirtificate && (
                                        <p className="text-danger">
                                            {
                                                errors.TaxExemption
                                                    .exm5disabledCirtificate
                                            }
                                        </p>
                                    )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {/* 6 remaining*/}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm6"}
                    label={t("form101.exm6")}
                    checked={values.TaxExemption.exm6}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={
                        !Array.isArray(values.children) ||
                        values.children?.length === 0 ||
                        errors.children?.length > 0
                    }
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm6 &&
                        errors.TaxExemption.exm6
                            ? errors.TaxExemption.exm6
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a parent who lives separately and
                    requesting tax credits for his children, who are in his
                    custody and for which he receives child allowance from the
                    the National Insurance Institute (according to clause 7
                    hereinafter) and who does not live in a shared household
                    with another individual.
                </p>

                {/* {Array.isArray(values.children) &&
                    (values.children?.length === 0 ||
                        errors.children?.length > 0) && (
                        <p className="text-danger">
                            It is not possible to choose because you have not
                            specified children.
                        </p>
                    )}
                {Array.isArray(values.children) &&
                    values.children?.length > 0 &&
                    errors.children?.length === 0 &&
                    values.TaxExemption &&
                    values.TaxExemption.exm6 && (
                        <div>
                            <div className="d-flex gap-5">
                                <div>
                                    <label htmlFor="exm5disabledCirtificate">
                                        Disabled or blind certificate for the
                                        employee or the spouse.
                                    </label>
                                    <br />
                                    <input
                                        type="file"
                                        name="TaxExemption.exm6disabledCirtificate"
                                        id="exm5disabledCirtificate"
                                        onChange={(e) =>
                                            setFieldValue(
                                                "TaxExemption.exm5disabledCirtificate",
                                                e.target.files[0]
                                            )
                                        }
                                        onBlur={handleBlur}
                                    />
                                    {touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm5disabledCirtificate &&
                                        errors.TaxExemption
                                            .exm5disabledCirtificate && (
                                            <p className="text-danger">
                                                {
                                                    errors.TaxExemption
                                                        .exm5disabledCirtificate
                                                }
                                            </p>
                                        )}
                                </div>
                            </div>
                        </div>
                    )} */}
            </div>
            {/* 7*/}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm7"}
                    label={t("form101.exm7")}
                    checked={values.TaxExemption.exm7}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={
                        !Array.isArray(values.children) ||
                        values.children?.length === 0 ||
                        errors.children?.length > 0
                    }
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm7 &&
                        errors.TaxExemption.exm7
                            ? errors.TaxExemption.exm7
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a parent in a single-parent family who
                    receives child allowance for them, or by a married woman or
                    by a single parent
                </p>

                {((Array.isArray(values.children) &&
                    values.children?.length > 0 &&
                    errors.children?.length === 0 &&
                    values.TaxExemption &&
                    values.TaxExemption.exm7) ||
                    (!errors?.children && values.children?.length > 0)) && (
                    <div>
                        <div className="row mt-3">
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm7NoOfChild"
                                    label="Number of children born in the tax year"
                                    value={values.TaxExemption.exm7NoOfChild}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm7NoOfChild &&
                                        errors.TaxExemption.exm7NoOfChild
                                            ? errors.TaxExemption.exm7NoOfChild
                                            : ""
                                    }
                                    required
                                />
                            </div>
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm7NoOfChild1to5"
                                    label="Number of children between the ages of 1 and 5 in the tax year"
                                    value={
                                        values.TaxExemption.exm7NoOfChild1to5
                                    }
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm7NoOfChild1to5 &&
                                        errors.TaxExemption.exm7NoOfChild1to5
                                            ? errors.TaxExemption
                                                  .exm7NoOfChild1to5
                                            : ""
                                    }
                                    required
                                />
                            </div>
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm7NoOfChild6to17"
                                    label="Number of children between the ages of 6 and 17 in the tax year"
                                    value={
                                        values.TaxExemption.exm7NoOfChild6to17
                                    }
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm7NoOfChild6to17 &&
                                        errors.TaxExemption.exm7NoOfChild6to17
                                            ? errors.TaxExemption
                                                  .exm7NoOfChild6to17
                                            : ""
                                    }
                                    required
                                />
                            </div>
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm7NoOfChild18"
                                    label="Number of children or who turn 18 years old in the tax year"
                                    value={values.TaxExemption.exm7NoOfChild18}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm7NoOfChild18 &&
                                        errors.TaxExemption.exm7NoOfChild18
                                            ? errors.TaxExemption
                                                  .exm7NoOfChild18
                                            : ""
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {/* 8*/}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm8"}
                    label={t("form101.exm8")}
                    checked={values.TaxExemption.exm8}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={values.TaxExemption.exm7}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm8 &&
                        errors.TaxExemption.exm8
                            ? errors.TaxExemption.exm8
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a parent (excluding a parent who has
                    checked clause 7 above), an unmarried woman who does not
                    have custody of her children and a single parent
                </p>

                {((Array.isArray(values.children) &&
                    values.children?.length > 0 &&
                    errors.children?.length === 0 &&
                    values.TaxExemption &&
                    values.TaxExemption.exm8) ||
                    (!errors?.children && values.children?.length > 0)) && (
                    <div>
                        <div className="row mt-3">
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm8NoOfChild"
                                    label="Number of children born in the tax year"
                                    value={values.TaxExemption.exm8NoOfChild}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption.exm8NoOfChild &&
                                        errors.TaxExemption.exm8NoOfChild
                                            ? errors.TaxExemption.exm8NoOfChild
                                            : ""
                                    }
                                    required
                                />
                            </div>
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm8NoOfChild1to5"
                                    label="Number of children between the ages of 1 and 5 in the tax year"
                                    value={
                                        values.TaxExemption.exm8NoOfChild1to5
                                    }
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm8NoOfChild1to5 &&
                                        errors.TaxExemption.exm8NoOfChild1to5
                                            ? errors.TaxExemption
                                                  .exm8NoOfChild1to5
                                            : ""
                                    }
                                    required
                                />
                            </div>
                            <div className="col-4">
                                <TextField
                                    name="TaxExemption.exm8NoOfChild6to17"
                                    label="Number of children between the ages of 6 and 17 in the tax year"
                                    value={
                                        values.TaxExemption.exm8NoOfChild6to17
                                    }
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.TaxExemption &&
                                        errors.TaxExemption &&
                                        touched.TaxExemption
                                            .exm8NoOfChild6to17 &&
                                        errors.TaxExemption.exm8NoOfChild6to17
                                            ? errors.TaxExemption
                                                  .exm8NoOfChild6to17
                                            : ""
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>
                )}
            </div>
            {/* 9*/}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm9"}
                    label={t("form101.exm9")}
                    checked={values.TaxExemption.exm9}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={values.TaxExemption.exm7}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm9 &&
                        errors.TaxExemption.exm9
                            ? errors.TaxExemption.exm9
                            : ""
                    }
                />
            </div>
            {/* 10 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm10"}
                    label={t("form101.exm10")}
                    checked={values.TaxExemption.exm10}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm10 &&
                        errors.TaxExemption.exm10
                            ? errors.TaxExemption.exm10
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a parent who lives apart from his
                    children, who is not eligible for tax credits for his
                    children, who has presented a court order ordering him to
                    pay child support.
                </p>
                {values.TaxExemption.exm10 && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="employeepassportCopy"
                                className="pt-2"
                            >
                                Photocopy of a court order for child support
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.exm10Certificate"
                                id="employeepassportCopy"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.exm10Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.exm10Certificate &&
                                errors.TaxExemption.exm10Certificate && (
                                    <p className="text-danger">
                                        {errors.TaxExemption.exm10Certificate}
                                    </p>
                                )}
                        </div>
                    </>
                )}
            </div>
            {/* 11 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm11"}
                    label={t("form101.exm11")}
                    checked={values.TaxExemption.exm11}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm11 &&
                        errors.TaxExemption.exm11
                            ? errors.TaxExemption.exm11
                            : ""
                    }
                />
                <p className="text-secondary">
                    My spouse does not receive these tax credits. My children,
                    for whom I request the tax credits, have no income in the
                    year.
                </p>
                {values.TaxExemption.exm11 && (
                    <>
                        <TextField
                            name="TaxExemption.exm11NoOfChildWithDisibility"
                            label="Number of children with disability who are not yet 19 years old, for whom you receive children's disability benefit from the National Insurance Institute"
                            value={
                                values.TaxExemption.exm11NoOfChildWithDisibility
                            }
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption
                                    .exm11NoOfChildWithDisibility &&
                                errors.TaxExemption.exm11NoOfChildWithDisibility
                                    ? errors.TaxExemption
                                          .exm11NoOfChildWithDisibility
                                    : ""
                            }
                            required
                        />
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="TaxExemption.exm11Certificate"
                                className="pt-2"
                            >
                                Children's disability benefit certificate from
                                the National Insurance Institute for the current
                                tax year *
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.exm11Certificate"
                                id="TaxExemption.exm11Certificate"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.exm11Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.exm11Certificate &&
                                errors.TaxExemption.exm11Certificate && (
                                    <p className="text-danger">
                                        {errors.TaxExemption.exm11Certificate}
                                    </p>
                                )}
                        </div>
                    </>
                )}
            </div>
            {/* 12 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm12"}
                    label={t("form101.exm12")}
                    checked={values.TaxExemption.exm12}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm12 &&
                        errors.TaxExemption.exm12
                            ? errors.TaxExemption.exm12
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a person who has remarried.
                </p>
                {values.TaxExemption.exm12 && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="employeepassportCopy"
                                className="pt-2"
                            >
                                Photocopy of a court order for alimony
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.exm12Certificate"
                                id="employeepassportCopy"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.exm12Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.exm12Certificate &&
                                errors.TaxExemption.exm12Certificate && (
                                    <p className="text-danger">
                                        {errors.TaxExemption.exm12Certificate}
                                    </p>
                                )}
                        </div>
                    </>
                )}
            </div>
            {/* 13 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm13"}
                    label={t("form101.exm13")}
                    checked={values.TaxExemption.exm13}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    disabled={
                        values.Spouse.Dob == "" || values.employeeDob === ""
                    }
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm13 &&
                        errors.TaxExemption.exm13
                            ? errors.TaxExemption.exm13
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a person who has remarried.
                </p>
            </div>
            {/* 14 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm14"}
                    label={t("form101.exm14")}
                    checked={values.TaxExemption.exm14}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm14 &&
                        errors.TaxExemption.exm14
                            ? errors.TaxExemption.exm14
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a person who has remarried.
                </p>
                {values.TaxExemption.exm14 && (
                    <div className="row">
                        <div className="col-4">
                            <DateField
                                name="TaxExemption.exm14BeginingDate"
                                label="Date of beginning of service"
                                value={values.TaxExemption.exm14BeginingDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm14BeginingDate &&
                                    errors.TaxExemption.exm14BeginingDate
                                        ? errors.TaxExemption.exm14BeginingDate
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-4">
                            <DateField
                                name="TaxExemption.exm14EndDate"
                                label="Date of end of service"
                                value={values.TaxExemption.exm14EndDate}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.TaxExemption &&
                                    errors.TaxExemption &&
                                    touched.TaxExemption.exm14EndDate &&
                                    errors.TaxExemption.exm14EndDate
                                        ? errors.TaxExemption.exm14EndDate
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-4">
                            <label
                                htmlFor="TaxExemption.exm14Certificate"
                                className="pt-2"
                            >
                                Discharge / end of service certificate
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.TaxExemption"
                                id="employeepassportCopy"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.TaxExemption",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.TaxExemption &&
                                errors.TaxExemption.TaxExemption && (
                                    <p className="text-danger">
                                        {errors.TaxExemption.TaxExemption}
                                    </p>
                                )}
                        </div>
                    </div>
                )}
            </div>
            {/* 15 */}
            <div className="border rounded p-3 m-2">
                <CheckBox
                    name={"TaxExemption.exm15"}
                    label={t("form101.exm15")}
                    checked={values.TaxExemption.exm15}
                    onChange={handleChange}
                    onBlur={handleBlur}
                    error={
                        touched.TaxExemption &&
                        errors.TaxExemption &&
                        touched.TaxExemption.exm15 &&
                        errors.TaxExemption.exm15
                            ? errors.TaxExemption.exm15
                            : ""
                    }
                />
                <p className="text-secondary">
                    To be completed by a person who has remarried.
                </p>
                {values.TaxExemption.exm15 && (
                    <>
                        <div className="mb-2 mx-2">
                            <label
                                htmlFor="TaxExemption.exm15Certificate"
                                className="pt-2"
                            >
                                Declaration in Form 119
                            </label>
                            <br />
                            <input
                                type="file"
                                name="TaxExemption.exm15Certificate"
                                id="employeepassportCopy"
                                onChange={(e) =>
                                    setFieldValue(
                                        "TaxExemption.exm15Certificate",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.TaxExemption &&
                                errors.TaxExemption &&
                                touched.TaxExemption.exm15Certificate &&
                                errors.TaxExemption.exm15Certificate && (
                                    <p className="text-danger">
                                        {errors.TaxExemption.exm15Certificate}
                                    </p>
                                )}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
